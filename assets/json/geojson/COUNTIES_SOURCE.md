# US Counties map data source

`counties_291.geojson` (lower 48 states + DC-adjacent counties), `counties_6.geojson`
(Alaska) and `counties_110.geojson` (Hawaii) provide the county boundary polygons
used by the "Show Counties Map" feature on the US Counties (USA-CA) award page,
mirroring the per-DXCC split already used by `states_291/6/110.geojson` for the
WAS award map.

## Source data

All counties come from a single source: the US Census Bureau's 2022
Cartographic Boundary Files at 1:500,000 scale (`cb_2022_us_county_500k`),
downloaded from `https://www2.census.gov/geo/tiger/GENZ2022/shp/cb_2022_us_county_500k.zip`
and converted from Shapefile to GeoJSON (via `pyshp`). This is the most
detailed of the Census Bureau's standard cartographic boundary resolutions
(500k/5m/20m); an earlier version of these files used the much coarser 20m
scale, which produced visibly blocky/faceted county outlines - 500k fixes
that while still being far lighter than the full-resolution TIGER/Line data.

Because this is the 2022 vintage, it already reflects Connecticut's 2022
switch from 8 counties to 9 "planning regions" and Alaska's 2015/2019 renames
and boundary changes (Wade Hampton -> Kusilvak Census Area, Valdez-Cordova
split into Chugach and Copper River Census Areas), so no separate/newer
source or manual simplification was needed for those two states this time.

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

Every county in `US_counties.csv` has a matching feature except one: Virginia's
"Bedford City" was legally merged into Bedford County in 2013 and has had no
boundary of its own since, so it's still counted as a distinct target
(matching MARAC's own list) but simply has no polygon on the map.
