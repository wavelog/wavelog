# US_counties.csv data source

`US_counties.csv` lists every US county/county-equivalent Wavelog tracks for the
US Counties (USA-CA) award (target list, county autocomplete in `Lookup.php`,
and `Counties.php`'s worked/confirmed queries). Each row is
`<state name>,<bare ARRL/MARAC short name>,<full display name>,<scoring group>`.

## Columns 1-3 (unchanged)

These come from the US Census Bureau's county/county-equivalent list, with
ARRL/MARAC short-name spelling corrections applied where they differ from the
Census Bureau's own spelling (see `assets/json/geojson/COUNTIES_SOURCE.md`'s
"Name matching" section for the full list of corrections and why - these
three columns back the map's boundary-name matching and are not changed by
the 4th column described below).

## Column 4: `scoring_group` (wavelog/wavelog#3782)

`Counties.php::get_counties_progress()` used to compute the award's
Worked/Confirmed/Target counts directly against all 3,149 rows above - the
raw Census county/county-equivalent list. That overcounts against the actual
USA Counties Award (USA-CA), which targets exactly **3,077** counties per the
authoritative list published by MARAC (Mobile Amateur Radio Awards Club, the
award's issuer):

- **Source of the 3,077-county target list**:
  `https://marac.org/pages/tools/county-abbrev-v4.doc` (MARAC's own county
  abbreviation reference, "V4 Changes 20feb2023" revision, credited to
  "Larry, W0QE"; the file ends with a literal count of `3077`). This is a
  `.doc` file; read locally via `textutil -convert txt` (macOS) or an
  equivalent converter.
- **Source of the award rules**:
  `https://marac.org/documents/usa-ca-rules.pdf` ("UNITED STATES OF AMERICA
  COUNTY AWARD," revised April 14, 2025). Rule **C.1** defines the county
  list as "all of the Louisiana parishes, the four judicial districts of
  Alaska, and the counties of the other 48 states, including the historical
  counties in Connecticut" - no mention of Washington DC, which is why DC is
  excluded below. Rule **C.5** governs independent cities: "In the case of
  independent cities or federal parks and reservations not located within a
  county, the applicant may claim any one of the adjoining counties for
  credit" - the operator's choice, not a single fixed parent-county mapping
  (searched for an established canonical VA-city-to-county pairing table used
  by county-hunting tools/communities and found none exists publicly; this is
  genuinely ambiguous by design, not something derivable from geometry).

`scoring_group` is the USA-CA target name each row counts toward:

1. **Most rows (3,074 of 3,149)**: `scoring_group` equals the row's own bare
   name (column 2) - no grouping, scores exactly as before.
2. **Alaska (35 rows)**: Alaska has no counties; USA-CA instead uses its 4
   Judicial Districts as the target unit. `scoring_group` is one of the exact
   4 target names from the MARAC doc: `First JD (SE)`, `Second JD (NW)`,
   `Third JD (SC)`, `Fourth JD (C)`. The current-borough-to-district mapping
   comes from `https://www.qsl.net/kl7j/county.html` (already referenced in
   wavelog/wavelog#3782 and cross-checked against it twice in that issue's
   discussion):
   - **First JD (SE)**: Haines, Hoonah-Angoon, Juneau, Ketchikan Gateway,
     Petersburg, Prince of Wales-Hyder, Sitka, Skagway, Wrangell, Yakutat
   - **Second JD (NW)**: Nome, North Slope, Northwest Arctic
   - **Third JD (SC)**: Aleutians East, Aleutians West, Anchorage, Bristol
     Bay, Chugach, Copper River, Dillingham, Kenai Peninsula, Kodiak Island,
     Lake and Peninsula, Matanuska-Susitna
   - **Fourth JD (C)**: Bethel, Denali, Fairbanks North Star, Kusilvak,
     Southeast Fairbanks, Yukon-Koyukuk

   `US_counties.csv`'s Alaska section also carries 5 stale/superseded rows
   beyond the 30 current entities above - `Fairbanks` (superseded by
   "Fairbanks North Star"), `Pribilof Islands` and `Saint Matthew Island` (no
   longer separate census areas), `Valdez-Cordova` (pre-2019, since split
   into Chugach/Copper River), and `Wales-Hyder` (pre-rename, now "Prince of
   Wales-Hyder"). Rather than remove them, each maps to the same JD as its
   current successor, so a QSO logged under an old/legacy name still scores
   correctly:
   - `Fairbanks` -> `Fourth JD (C)` (same as Fairbanks North Star)
   - `Valdez-Cordova` -> `Third JD (SC)` (same as Chugach/Copper River)
   - `Wales-Hyder` -> `First JD (SE)` (same as Prince of Wales-Hyder)
   - `Pribilof Islands` -> `Third JD (SC)` (historically part of the
     Aleutians West area)
   - `Saint Matthew Island` -> `Fourth JD (C)` (uninhabited; no current
     borough of its own)
3. **`UNSCORED` (41 rows)**: not one of the 3,077 targets, and - per rule
   C.5 above - no single correct county to silently credit instead, so these
   are excluded from `target`/`worked`/`confirmed` counts rather than guessed
   at. If a QSO is logged against one of these, `get_counties_progress()`
   surfaces it as `unmatched` instead of silently dropping it (see
   `Counties.php`).
   - The single `District of Columbia` row (not in MARAC's list per rule C.1
     - DC is not a state, county, or Louisiana parish, and the rules don't
     extend county-equivalent status to it the way they do for Alaska's
     judicial districts).
   - `Carson City` (Nevada's consolidated city-county, not part of any
     Nevada county - the same "independent city, adjoining-county-of-choice"
     situation as Virginia's cities below).
   - Virginia's 39 independent cities (all of them: Alexandria City, Bedford
     City, Bristol, Buena Vista City, Charlottesville City, Chesapeake City,
     Colonial Heights City, Covington City, Danville City, Emporia City,
     Fairfax City, Falls Church City, Franklin City, Fredericksburg City,
     Galax City, Hampton City, Harrisonburg City, Hopewell City, Lexington
     City, Lynchburg City, Manassas City, Manassas Park City, Martinsville
     City, Newport News City, Norfolk City, Norton City, Petersburg City,
     Poquoson City, Portsmouth City, Radford City, Richmond City, Roanoke
     City, Salem, Staunton City, Suffolk City, Virginia Beach City,
     Waynesboro City, Williamsburg City, Winchester City). Note `Bristol`
     and `Salem`'s bare names (column 2) don't carry a "City" suffix even
     though they are independent cities - column 3 (full display name)
     discloses it (`Bristol City`, `Salem City`). Virginia's `Charles City`
     and `James City` are **not** independent cities - "City" is part of
     their actual county name (both appear in MARAC's 3,077 list under that
     exact name) - so they keep `scoring_group` = themselves, unlike every
     other VA row ending in "City".

## Verification

Every row's `scoring_group` was computed programmatically against the parsed
`county-abbrev-v4.doc` list (name matching case/punctuation-insensitive:
"Saint"/"St.", hyphens, apostrophes, and internal spacing all normalized away
- e.g. "De Kalb"/"DeKalb", "La Moure"/"Lamoure", "St John the Baptist"/"Saint
John Baptist" all resolve to the same target). Two checks confirm the mapping
is complete and correct:

- The count of distinct non-`UNSCORED` `(state, scoring_group)` pairs across
  the whole file is exactly **3,077**.
- Grouping `US_counties.csv` by state and counting distinct `scoring_group`
  values per state matches MARAC's per-state county count in
  `county-abbrev-v4.doc` exactly, for all 50 states with zero mismatches.

Every row resolved to either a matched target, an Alaska JD, or `UNSCORED` -
none were left unaccounted for.
