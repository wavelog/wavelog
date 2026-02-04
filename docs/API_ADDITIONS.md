# Wavelog API Additions - Band/Mode Matrix and Recent Contacts

## Overview

This pull request adds two new API endpoints to support external applications that need comprehensive worked-before information:

1. **`band_mode_matrix`** - Returns a full matrix of worked/confirmed status across all band/mode combinations
2. **`recent_contacts`** - Returns the most recent QSOs with a specific callsign

These endpoints complement the existing `private_lookup` endpoint, which only returns boolean values for a single band/mode combination at a time.

## Use Case

External logging portals (like HamRig) need to display comprehensive worked-before information similar to what Wavelog shows in its own interface. Currently, `private_lookup` only returns:
- `call_worked` (boolean)
- `call_worked_band` (boolean for ONE specified band)
- `call_worked_band_mode` (boolean for ONE specified band/mode)

This requires multiple API calls to build a complete picture. The new endpoints solve this by returning all data in a single request.

---

## New Endpoint: `band_mode_matrix`

### URL
```
POST /index.php/api/band_mode_matrix
```

### Request Body
```json
{
    "key": "your-api-key",
    "callsign": "W1ABC",
    "station_ids": [1, 2]  // Optional: filter to specific stations
}
```

### Response
```json
{
    "status": "success",
    "callsign": "W1ABC",
    "dxcc_id": "291",
    "dxcc": "United States",
    "bands": ["160m", "80m", "60m", "40m", "30m", "20m", "17m", "15m", "12m", "10m", "6m", "4m", "2m", "70cm", "23cm"],
    "modes": ["SSB", "CW", "FT8", "FT4", "RTTY", "FM", "AM", "DIGI"],
    "matrix": {
        "20m": {
            "SSB": {
                "worked": true,
                "confirmed": true,
                "qso_count": 3,
                "last_qso": "2024-01-15 14:30:00"
            },
            "CW": {
                "worked": false,
                "confirmed": false,
                "qso_count": 0,
                "last_qso": null
            },
            "FT8": {
                "worked": true,
                "confirmed": false,
                "qso_count": 1,
                "last_qso": "2024-01-10 08:15:00"
            }
            // ... other modes
        }
        // ... other bands
    },
    "total_qsos": 4
}
```

### Features
- Returns worked/confirmed status for every band/mode combination
- Respects user's default confirmation preference (LoTW, Paper QSL, eQSL)
- Includes QSO count and most recent QSO date for each combination
- Mode categorization maps detailed modes to general categories (e.g., USB/LSB → SSB)

---

## New Endpoint: `recent_contacts`

### URL
```
POST /index.php/api/recent_contacts
```

### Request Body
```json
{
    "key": "your-api-key",
    "callsign": "W1ABC",
    "limit": 10,            // Optional: max 50, default 10
    "station_ids": [1, 2]   // Optional: filter to specific stations
}
```

### Response
```json
{
    "status": "success",
    "callsign": "W1ABC",
    "dxcc_id": "291",
    "dxcc": "United States",
    "total_found": 5,
    "limit": 10,
    "contacts": [
        {
            "id": 12345,
            "callsign": "W1ABC",
            "datetime": "2024-01-15 14:30:00",
            "datetime_off": "2024-01-15 14:45:00",
            "band": "20m",
            "frequency": "14.230",
            "mode": "SSB",
            "submode": null,
            "rst_sent": "59",
            "rst_rcvd": "59",
            "name": "John",
            "qth": "Boston, MA",
            "gridsquare": "FN42",
            "comment": "Great signal",
            "qsl": {
                "paper_sent": "Y",
                "paper_rcvd": "Y",
                "lotw_sent": "Y",
                "lotw_rcvd": "Y",
                "eqsl_sent": null,
                "eqsl_rcvd": null
            },
            "station_id": 1
        }
        // ... more contacts
    ]
}
```

### Features
- Returns detailed information for recent QSOs
- Ordered by date descending (most recent first)
- Includes full QSL status for all methods
- Configurable limit (max 50 to prevent abuse)

---

## Authentication

Both endpoints use the same authentication as other Wavelog API endpoints:
- Requires a valid API key with at least read permissions
- Key is passed in the JSON body as `"key": "your-api-key"`
- Session-based auth also works for logged-in users

---

## Mode Categorization

The `band_mode_matrix` endpoint maps detailed modes to these general categories:

| Category | Included Modes |
|----------|----------------|
| SSB | SSB, USB, LSB |
| CW | CW |
| FT8 | FT8 |
| FT4 | FT4 |
| RTTY | RTTY, FSK |
| FM | FM, NFM, WFM |
| AM | AM |
| DIGI | PSK31, PSK63, JT65, JT9, JS8, OLIVIA, MFSK, WSPR, etc. |

This allows consistent display across different logging styles while preserving the actual mode in `recent_contacts`.

---

## Backward Compatibility

These additions are fully backward compatible:
- New endpoints only, no changes to existing endpoints
- `private_lookup` continues to work exactly as before
- No database schema changes required

---

## Example Usage (curl)

### Band/Mode Matrix
```bash
curl -X POST https://your-wavelog.com/index.php/api/band_mode_matrix \
  -H "Content-Type: application/json" \
  -d '{"key":"your-api-key","callsign":"W1ABC"}'
```

### Recent Contacts
```bash
curl -X POST https://your-wavelog.com/index.php/api/recent_contacts \
  -H "Content-Type: application/json" \
  -d '{"key":"your-api-key","callsign":"W1ABC","limit":5}'
```
