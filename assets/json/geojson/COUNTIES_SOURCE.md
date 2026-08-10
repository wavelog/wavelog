# US Counties map data source

`counties_291.geojson` (lower 48 states + DC-adjacent counties), `counties_6.geojson`
(Alaska) and `counties_110.geojson` (Hawaii) provide the county boundary polygons
used by the "Show Counties Map" feature on the US Counties (USA-CA) award page,
mirroring the per-DXCC split already used by `states_291/6/110.geojson` for the
WAS award map.

## Source data

- **Lower 48 states** (all counties except Connecticut, which is handled
  separately below): US Census Bureau, 2018 Cartographic Boundary Files
  (`cb_2018_us_county_20m`), as repackaged in FIPS-keyed GeoJSON by
  [plotly/datasets](https://github.com/plotly/datasets/blob/master/geojson-counties-fips.json)
  (plotly/datasets is MIT licensed; the underlying boundary data itself is a
  work of the US federal government).
- **Connecticut** and **Alaska**: fetched directly from the US Census Bureau's
  [TIGERweb `State_County` MapServer](https://tigerweb.geo.census.gov/arcgis/rest/services/TIGERweb/State_County/MapServer)
  (2025 vintage), because the 2018 cartographic file predates Connecticut's
  2022 switch from 8 counties to 9 "planning regions" and Alaska's 2015/2019
  renames and boundary changes (Wade Hampton -> Kusilvak Census Area,
  Valdez-Cordova split into Chugach and Copper River Census Areas). These two
  states' geometry was simplified (Ramer-Douglas-Peucker, ~0.001 degree
  tolerance) to bring point density in line with the generalized cartographic
  files used for the rest of the country, since TIGERweb serves full-resolution
  TIGER/Line boundaries.

All boundary data originates from the US Census Bureau and is in the public
domain in the United States (17 U.S.C. § 105 - works of the US government are
not subject to copyright). No additional license restrictions apply.

## Name matching

Each feature's `id` is `"<2-letter state code>|<ARRL/MARAC short county name>"`,
matching `COL_STATE`/`COL_CNTY` as stored by Wavelog and the short county
names in `assets/json/US_counties.csv`. ARRL county names occasionally differ
from the Census Bureau's spelling/spacing (e.g. "St. Clair" vs "Saint Clair",
"DeKalb" vs "De Kalb", Virginia independent cities getting a "City" suffix to
disambiguate from same-named counties) - see the `NAME_OVERRIDES` table used
to build these files for the full list of corrections applied.

One ARRL county, Virginia's "Bedford City" (FIPS 51515), was legally merged
into Bedford County in 2013 but is still counted as a distinct target in
`US_counties.csv` and still ships as its own small polygon in the 2018
cartographic file used here, so it renders correctly on the map as a
historical boundary rather than being dropped.
