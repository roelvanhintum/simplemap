<?php
/**
 * Maps for Craft CMS 3
 *
 * @link      https://ethercreative.co.uk
 * @copyright Copyright (c) 2019 Ether Creative
 */

namespace ether\simplemap\services;

use craft\base\Component;
use craft\base\Element;
use craft\base\ElementInterface;
use craft\elements\db\ElementQuery;
use craft\elements\db\ElementQueryInterface;
use ether\simplemap\models\Map;
use ether\simplemap\fields\MapField;
use ether\simplemap\records\Map as MapRecord;

/**
 * Class MapService
 *
 * @author  Ether Creative
 * @package ether\simplemap\services
 */
class MapService extends Component
{

	// Properties
	// =========================================================================

	private $_location;
	private $_distance;

	// Methods
	// =========================================================================

	/**
	 * @param MapField         $field
	 * @param ElementInterface|Element $owner
	 *
	 * @return bool
	 */
	public function validateField (MapField $field, ElementInterface $owner)
	{
		/** @var Map $map */
		$map = $owner->getFieldValue($field->handle);

		$valid = $map->validate();

		foreach ($map->getErrors() as $error)
			$owner->addError($field->handle, $error[0]);

		return $valid;
	}

	/**
	 * @param MapField                 $field
	 * @param ElementInterface|Element $owner
	 *
	 * @throws \Throwable
	 */
	public function saveField (MapField $field, ElementInterface $owner)
	{
		/** @var Map $map */
		$map = $owner->getFieldValue($field->handle);

		$map->fieldId     = $field->id;
		$map->ownerId     = $owner->id;
		$map->ownerSiteId = $owner->siteId;

		$record = MapRecord::findOne([
			'ownerId'     => $map->ownerId,
			'ownerSiteId' => $map->ownerSiteId,
			'fieldId'     => $map->fieldId,
		]);

		if ($record)
			$map->id = $record->id;

		$this->saveRecord($map, !$map->id);
	}

	/**
	 * @param Map $map
	 * @param     $isNew
	 *
	 * @throws \Exception
	 */
	public function saveRecord (Map $map, $isNew)
	{
		$record = null;

		if (!$isNew)
		{
			$record = MapRecord::findOne($map->id);

			if (!$record)
				throw new \Exception('Invalid map ID: ' . $map->id);
		}

		if ($record === null)
		{
			$record = new MapRecord();

			if ($map->id)
				$record->id = $map->id;

			$record->ownerId     = $map->ownerId;
			$record->ownerSiteId = $map->ownerSiteId;
			$record->fieldId     = $map->fieldId;
		}

		$record->lat = $map->lat;
		$record->lng = $map->lng;

		$record->save(false);
	}

	/**
	 * Returns the distance from the search origin (if one exists)
	 *
	 * @param Map $map
	 *
	 * @return float|int|null
	 */
	public function getDistance (Map $map)
	{
		if (!$this->_location || !$this->_distance)
			return null;

		$originLat = $this->_location['lat'];
		$originLng = $this->_location['lng'];

		$targetLat = $map->lat;
		$targetLng = $map->lng;

		return (
			$this->_distance *
			rad2deg(
				acos(
					cos(deg2rad($originLat)) *
					cos(deg2rad($targetLat)) *
					cos(deg2rad($originLng) - deg2rad($targetLng)) +
					sin(deg2rad($originLat)) *
					sin(deg2rad($targetLat))
				)
			)
		);
	}

	/**
	 * @param ElementQueryInterface $query
	 * @param mixed                 $value
	 * @param MapField              $field
	 *
	 * @throws \Exception
	 */
	public function modifyElementsQuery (ElementQueryInterface $query, $value, MapField $field)
	{
		if (empty($value))
			return;

		/** @var ElementQuery $query */

		$table = MapRecord::TableName;
		$alias = MapRecord::TableNameClean . '_' . $field->handle;
		$on = [
			'and',
			'[[elements.id]] = [[' . $alias . '.ownerId]]',
			'[[elements.dateDeleted]] IS NULL',
			'[[elements_sites.siteId]] = [[' . $alias . '.ownerSiteId]]',
			'[[' . $alias . '.fieldId]] = ' . $field->id,
		];

		$query->query->join('JOIN', $table . ' ' . $alias, $on);
		$query->subQuery->join('JOIN', $table . ' ' . $alias, $on);

		if ($value === ':empty:')
		{
			$query->query->andWhere([
				'[[' . $alias . '.lat]]' => null,
			]);

			return;
		}
		else if ($value === ':notempty:' || $value === 'not :empty:')
		{
			$query->query->andWhere([
				'not',
				['[[' . $alias . '.lat]]' => null],
			]);

			return;
		}

		$oldOrderBy = null;
		$search = false;

		if (!is_array($query->orderBy))
		{
			$oldOrderBy = $query->orderBy;
			$query->orderBy = [];
		}

		// Coordinate CraftQL support
		if (array_key_exists('coordinate', $value))
			$value['location'] = $value['coordinate'];

		if (array_key_exists('location', $value))
			$search = $this->_searchLocation($query, $value, $alias);

		if (array_key_exists('distance', $query->orderBy))
			$this->_replaceOrderBy($query, $search);

		if ($oldOrderBy !== null)
			$query->orderBy = $oldOrderBy;
	}

	/**
	 * Populates any missing location data
	 *
	 * @param Map      $map
	 * @param MapField $field
	 *
	 * @throws \Exception
	 */
	public function populateMissingData (Map $map, MapField $field)
	{
		$postcode = is_array($map->parts)
			? @$map->parts['postcode']
			: $map->parts->postcode;

		// Missing Lat / Lng
		if (!($map->lat && $map->lng) && ($map->address || $postcode))
		{
			$latLng   = GeoService::latLngFromAddress($map->address ?: $postcode);
			$map->lat = $latLng['lat'];
			$map->lng = $latLng['lng'];
		}

		// Missing address / parts
		if ((!$map->address || $map->address === $postcode) && ($map->lat && $map->lng))
		{
			$loc          = GeoService::addressFromLatLng($map->lat, $map->lng);
			$map->address = $loc['address'];
			$map->parts   = array_merge(
				array_filter((array) $loc['parts']),
				array_filter((array) $map->parts)
			);
		}

		// Missing zoom
		if (!$map->zoom)
			$map->zoom = $field->zoom;
	}

	// Private Methods
	// =========================================================================

	/**
	 * Filters the query by location.
	 *
	 * Returns either `false` if we can't filter by location, or the location
	 * search string if we can.
	 *
	 * @param ElementQuery $query
	 * @param mixed        $value
	 * @param string       $table
	 *
	 * @return bool|string
	 * @throws \Exception
	 */
	private function _searchLocation (ElementQuery $query, $value, $table)
	{
		$location = $value['location'];
		$country  = $value['country'] ?? null;
		$radius   = $value['radius'] ?? 50.0;
		$unit     = $value['unit'] ?? 'km';

		// Normalize location
		$location = GeoService::normalizeLocation($location, $country);

		if ($location === null)
			return false;

		$lat = $location['lat'];
		$lng = $location['lng'];

		// Normalize radius
		if (!is_numeric($radius))
			$radius = (float) $radius;

		if (!is_numeric($radius))
			$radius = 50.0;

		// Normalize unit
		$unit = GeoService::normalizeDistance($unit);

		// Base Distance
		$distance = $unit === 'km' ? '111.045' : '69.0';

		// Store for populating search result distance
		$this->_location = $location;
		$this->_distance = (float) $distance;

		// Search Query
		$search = str_replace(["\r", "\n", "\t"], '', "(
			$distance *
			DEGREES(
				ACOS(
					COS(RADIANS($lat)) *
					COS(RADIANS([[$table.lat]])) *
					COS(RADIANS($lng) - RADIANS([[$table.lng]])) +
					SIN(RADIANS($lat)) *
					SIN(RADIANS([[$table.lat]]))
				)
			)
		)");

		// Restrict the results
		$restrict = [
			'and',
			[
				'and',
				"[[$table.lat]] >= $lat - ($radius / $distance)",
				"[[$table.lat]] <= $lat + ($radius / $distance)",
			],
			[
				'and',
				"[[$table.lng]] >= $lng - ($radius / ($distance * COS(RADIANS($lat))))",
				"[[$table.lng]] <= $lng + ($radius / ($distance * COS(RADIANS($lat))))",
			]
		];

		// Filter the query
		$query
			->subQuery
			->andWhere($restrict)
			->andWhere($search . ' <= ' . $radius);

		return $search;
	}

	/**
	 * Will replace the distance search with the correct query if available,
	 * or otherwise remove it.
	 *
	 * @param ElementQuery $query
	 * @param bool         $search
	 */
	private function _replaceOrderBy (ElementQuery $query, $search = false)
	{
		$nextOrder = [];

		foreach ((array) $query->orderBy as $order => $sort)
		{
			if ($order === 'distance' && $search) $nextOrder[$search] = $sort;
			elseif ($order !== 'distance') $nextOrder[$order] = $sort;
		}

		$query->orderBy($nextOrder);
	}

}
