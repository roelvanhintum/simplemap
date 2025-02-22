<template>
	<div :class="cls"></div>
</template>

<script lang="js">
	import L from 'leaflet';
	// TODO: Only load mutants in if they're needed
	import 'leaflet.gridlayer.googlemutant';
	import 'leaflet.mapkitmutant';
	import MapTiles from '../enums/MapTiles';
	import 'leaflet/dist/leaflet.css';
	import waitForGlobal from '../helpers/waitForGlobal';
	import { t } from '../filters/craft';

	const icon = (w, h, f = '#E7433B') => `<svg width="${w}" height="${h}" viewBox="0 0 14 20"><path fill="${f}" d="M6.976.478C3.482.478.634 3.313.634 6.79c0 2.381 1.716 4.247 2.945 6.09 1.23 1.844 2 3.706 2.664 6.17a.78.78 0 0 0 .733.56c.308 0 .64-.217.733-.56.724-2.69 1.49-4.537 2.704-6.324 1.213-1.786 2.906-3.56 2.906-5.936 0-3.476-2.849-6.31-6.343-6.31zm.04 3.874c1.21 0 2.18.968 2.18 2.174A2.17 2.17 0 0 1 7.016 8.7a2.17 2.17 0 0 1-2.18-2.174 2.17 2.17 0 0 1 2.18-2.174z"/></svg>`;

	export default {
		props: {
			tiles: String,
			token: [String, Object],
			latLng: Object,
			zoom: Number,
			minZoom: Number,
			maxZoom: Number,
			hideSearch: Boolean,
		},

		data () {
			return {
				map: null,
				marker: null,
			};
		},

		mounted () {
			this.map = L.map(this.$el, {
				minZoom: this.minZoom,
				maxZoom: this.maxZoom,
				scrollWheelZoom: false,
				zoomControl: false,
			}).setView(this.latLng, this.zoom);

			const self = this;

			L.Control.CustomZoom = L.Control.extend({
				onAdd: map => {
					const wrap   = L.DomUtil.create('div')
						, zIn    = L.DomUtil.create('button')
						, zOut   = L.DomUtil.create('button')
						, center = L.DomUtil.create('button');

					wrap.classList.add(self.$style.control);

					zIn.textContent = '+';
					zOut.textContent = '-';
					center.innerHTML = icon(
						14 * 0.75,
						20 * 0.75,
						'rgba(41, 50, 61, 0.75)'
					);

					zIn.setAttribute('title', t('Zoom In'));
					zOut.setAttribute('title', t('Zoom Out'));
					center.setAttribute('title', t('Center on Marker'));

					wrap.appendChild(zIn);
					wrap.appendChild(zOut);
					wrap.appendChild(center);

					zIn.addEventListener('click', e => {
						e.preventDefault();
						map.setZoomAround(
							self.getOffsetPoint(map.getCenter(), true),
							map.getZoom() + map.options.zoomDelta,
						);
					});

					zOut.addEventListener('click', e => {
						e.preventDefault();
						map.setZoomAround(
							self.getOffsetPoint(map.getCenter(), true),
							map.getZoom() - map.options.zoomDelta,
						);
					});

					center.addEventListener('click', e => {
						e.preventDefault();
						self.panTo(self.latLng);
					});

					return wrap;
				},
				onRemove: () => {},
			});

			L.control.customZoom = function (opts) {
				return new L.Control.CustomZoom(opts);
			};

			L.control.customZoom({ position: 'topright' }).addTo(this.map);

			this.map.panBy(
				L.point(this.offsetAmount()),
				{ animate: false }
			);

			if (this.tiles.indexOf('google') > -1) {
				this._googleMutant();
			} else if (this.tiles.indexOf('mapkit') > -1) {
				this._mapKitMutant();
			} else {
				const opts = { attribution: this.tileLayer.attr };

				if (this.tileLayer.subdomains)
					opts.subdomains = this.tileLayer.subdomains;

				const tileLayer = L.tileLayer(
					this.tileLayer.url,
					opts
				);
				this.map.addLayer(tileLayer);
			}

			this.map.on('zoom', this.onZoom);

			this.setMarker();

			// Re-draw the map if it was hidden
			const io = new IntersectionObserver(entries => {
				if (entries[0].intersectionRatio <= 0)
					return;

				this.map.invalidateSize(true);
			});

			io.observe(this.$el);
		},

		computed: {
			cls () {
				const cls = [this.$style.map];

				if (this.hideSearch)
					cls.push(this.$style.short);

				return cls;
			},

			tileLayer () {
				const scale     = L.Browser.retina ? '@2x.png' : '.png'
					, hereScale = L.Browser.retina ? '512' : '256'
					, style     = this.tiles.split(/\.(.+)/)[1];

				switch (this.tiles) {
					case MapTiles.Wikimedia:
						return {
							url: `https://maps.wikimedia.org/osm-intl/{z}/{x}/{y}${scale}`,
							attr: '&copy; <a href="http://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a>, &copy; <a href="https://maps.wikimedia.org" target="_blank" rel="noreferrer">Wikimedia</a>',
						};
					case MapTiles.OpenStreetMap:
						return {
							url: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
							attr: '&copy; <a href="http://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a>',
						};
					case MapTiles.CartoVoyager:
					case MapTiles.CartoPositron:
					case MapTiles.CartoDarkMatter:
						return {
							url: `https://{s}.basemaps.cartocdn.com/${style}/{z}/{x}/{y}${scale}`,
							attr: '&copy; <a href="http://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a>, &copy; <a href="https://carto.com/attribution" target="_blank" rel="noreferrer">CARTO</a>',
						};
					case MapTiles.MapboxOutdoors:
					case MapTiles.MapboxStreets:
					case MapTiles.MapboxLight:
					case MapTiles.MapboxDark:
						return {
							url: `https://api.tiles.mapbox.com/v4/mapbox.${style}/{z}/{x}/{y}${scale}?access_token=${this.token}`,
							attr: '&copy; <a href="http://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a>, &copy; <a href="https://www.mapbox.com/" target="_blank" rel="noreferrer">Mapbox</a>',
						};
					case MapTiles.HereNormalDay:
					case MapTiles.HereNormalDayGrey:
					case MapTiles.HereNormalDayTransit:
					case MapTiles.HereReduced:
					case MapTiles.HerePedestrian:
						return {
							url: `https://{s}.base.maps.api.here.com/maptile/2.1/maptile/newest/${style}/{z}/{x}/{y}/${hereScale}/png8?app_id=${this.token.appId}&app_code=${this.token.appCode}`,
							attr: '&copy; <a href="http://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a>, &copy; <a href="https://here.com/" target="_blank" rel="noreferrer">Here</a>',
							subdomains: '1234',
						};
					case MapTiles.HereTerrain:
					case MapTiles.HereSatellite:
					case MapTiles.HereHybrid:
						return {
							url: `https://{s}.aerial.maps.api.here.com/maptile/2.1/maptile/newest/${style}/{z}/{x}/{y}/${hereScale}/png8?app_id=${this.token.appId}&app_code=${this.token.appCode}`,
							attr: '&copy; <a href="http://www.openstreetmap.org/copyright" target="_blank" rel="noreferrer">OpenStreetMap</a>, &copy; <a href="https://here.com/" target="_blank" rel="noreferrer">Here</a>',
							subdomains: '1234',
						};
					default:
						throw new Error('Unknown map tiles service: ' + this.tiles);
				}
			},

			icon () {
				const w = 14 * 2
					, h = 20 * 2;

				return L.divIcon({
					html: icon(w, h),
					iconSize: [w, h],
					iconAnchor: [w / 2, h],
					className: '',
				});
			},
		},

		watch: {
			latLng: {
				deep: true,
				/**
				 * Watches the latLng prop for changes, then updates the map location
				 * accordingly
				 */
				handler (next, old) {
					if (next.lat === old.lat && next.lng === old.lng)
						return;

					this.panTo(this.latLng);
					this.setMarker();
				},
			},
		},

		methods: {
			offsetAmount () {
				let x = 0;

				if (!window.matchMedia('(max-width: 767px)').matches)
					x = -(this.$el.getBoundingClientRect().width / 4);

				return { x, y: this.hideSearch ? 5 : -15 };
			},

			getOffsetPoint (latLng, invert = false) {
				const point = this.map.latLngToContainerPoint(latLng);
				if (invert) {
					point.x -= this.offsetAmount().x;
					point.y -= this.offsetAmount().y;
				} else {
					point.x += this.offsetAmount().x;
					point.y += this.offsetAmount().y;
				}
				return point;
			},

			panTo (latLng) {
				this.map.panTo(
					this.map.containerPointToLatLng(
						this.getOffsetPoint(latLng)
					)
				);
			},

			setMarker () {
				if (this.marker)
					this.map.removeLayer(this.marker);

				this.marker = L.marker(this.latLng, {
					icon: this.icon,
					draggable: true,
					autoPan: true,
				});

				this.map.addLayer(this.marker);

				this.marker.on('dragend', () => {
					this.$emit('change', this.marker.getLatLng());
				});
			},

			/**
			 * Listens to map zoom event and triggers component zoom event.
			 */
			onZoom () {
				this.$emit('zoom', this.map.getZoom());
			},

			/**
			 * Sets up Leaflet to use Google Maps
			 *
			 * @private
			 */
			_googleMutant () {
				waitForGlobal('google', () => {
					L.gridLayer.googleMutant({
						type: this.tiles.split('.')[1],
					}).addTo(this.map);
				});
			},

			/**
			 * Sets up Leaflet to use Apple MapKit
			 *
			 * @private
			 */
			_mapKitMutant () {
				waitForGlobal('mapkit', () => {
					L.mapkitMutant({
						type: this.tiles.split('.')[1],
						authorizationCallback: done => done(this.token),
						language: window.Craft.language,
					}).addTo(this.map);
				});
			},
		},
	};
</script>

<style lang="less" module>
	.map {
		position: absolute;
		z-index: 0;
		top: 0;
		left: 0;
		right: 0;
		bottom: 0;

		box-sizing: border-box;

		@media only screen and (max-width: 767px) {
			height: 300px;
			bottom: auto;
		}

		&.short {
			height: 100%;
		}
	}
	.control {
		margin: 24px !important;
		font-size: 0;

		border-radius: 5px;
		box-shadow: 0 2px 15px 0 rgba(0, 0, 0, 0.20);

		@media only screen and (max-width: 767px) {
			margin: 14px !important;
		}

		button {
			display: block;
			width: 100%;
			padding: 2px 10px 3px;

			color: rgba(41, 50, 61, 0.75);
			font-size: 20px;
			font-weight: bold;
			line-height: normal;

			appearance: none;
			background-color: #fff;
			border: none;
			cursor: pointer;

			transition: background-color 0.15s ease;

			&:not(:last-child) {
				border-bottom: 1px solid #DCE4EA;
			}

			&:first-child {
				padding-bottom: 4px;
				border-radius: 5px 5px 0 0;
			}

			&:last-child {
				padding-top: 4px;
				border-radius: 0 0 5px 5px;
			}

			&:hover {
				background-color: #f0f9ff;
			}
		}
	}
</style>
