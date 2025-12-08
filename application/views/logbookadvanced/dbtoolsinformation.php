     <h5>Wavelog Database Tools (DBTools)</h5>

    <p>The Database Tools module in Wavelog provides a comprehensive suite of utilities for maintaining data integrity and repairing common issues in your logbook data. These tools are accessible through the Advanced Logbook interface and are designed to help operators keep their QSO records accurate and complete.</p>

    <h5>Overview</h5>

    <p>DBTools offers automated checking and fixing functionality for various types of QSO metadata that may be missing, incorrect, or outdated. The tools perform validation against authoritative sources and provide batch processing capabilities to efficiently handle large logbooks.</p>

    <h5>Available Tools</h5>

    <div class="tool-box">
        <h5>1. CQ Zone Fixer</h5>
        <ul>
            <li><strong>Purpose</strong>: Updates missing or incorrect CQ zone information</li>
            <li><strong>Function</strong>: Validates QSO coordinates against CQ zone boundaries</li>
            <li><strong>Use Case</strong>: Essential for award applications like CQ DX Field Day, CQ WW DX Contest, and other CQ-sponsored awards</li>
        </ul>
    </div>

    <div class="tool-box">
        <h5>2. ITU Zone Fixer</h5>
        <ul>
            <li><strong>Purpose</strong>: Updates missing or incorrect ITU zone information</li>
            <li><strong>Function</strong>: Cross-references station locations with ITU zone definitions</li>
            <li><strong>Use Case</strong>: Required for ITU-sponsored awards and contests</li>
        </ul>
    </div>

    <div class="tool-box">
        <h5>3. Continent Fixer</h5>
        <ul>
            <li><strong>Purpose</strong>: Updates missing or incorrect continent information</li>
            <li><strong>Function</strong>: Determines continent based on station coordinates or DXCC entity</li>
            <li><strong>Use Case</strong>: Useful for continental award tracking and statistics</li>
        </ul>
    </div>

    <div class="tool-box">
        <h5>4. State/Province Fixer</h5>
        <ul>
            <li><strong>Purpose</strong>: Updates missing or incorrect state or province information</li>
            <li><strong>Function</strong>: Uses grid square and location data to determine administrative divisions</li>
            <li><strong>Use Case</strong>: Critical for WAS (Worked All States) and similar award programs</li>
        </ul>
    </div>

    <div class="tool-box">
        <h5>5. Distance Calculator</h5>
        <ul>
            <li><strong>Purpose</strong>: Calculates and updates distance information for QSOs</li>
            <li><strong>Function</strong>: Computes great-circle distances between stations</li>
            <li><strong>Use Case</strong>: Important for distance-based awards and personal statistics</li>
        </ul>
    </div>

    <div class="tool-box">
        <h5>6. DXCC Data Validator</h5>
        <ul>
            <li><strong>Purpose</strong>: Identifies QSOs missing DXCC information</li>
            <li><strong>Function</strong>: Cross-references callsigns and timestamps with DXCC entity database</li>
            <li><strong>Use Case</strong>: Essential for DXCC award tracking and eQSL/LoTW verification</li>
        </ul>
    </div>

    <div class="tool-box">
        <h5>7. DXCC Re-check Tool</h5>
        <ul>
            <li><strong>Purpose</strong>: Re-evaluates all QSOs for correct DXCC assignment</li>
            <li><strong>Function</strong>: Overwrites existing DXCC data using current Wavelog database</li>
            <li><strong>Use Case</strong>: Useful when DXCC boundaries have changed or initial imports were incorrect</li>
        </ul>
        <div class="warning">
            <strong>Warning</strong>: This tool will overwrite ALL existing DXCC information
        </div>
    </div>
<br/>
    <h5>How It Works</h5>

    <h5>Check Process</h5>
    <ol>
        <li><strong>Scanning</strong>: Each tool performs a comprehensive scan of the logbook database</li>
        <li><strong>Validation</strong>: Data is cross-referenced against authoritative sources (DXCC lists, coordinate databases, zone definitions)</li>
        <li><strong>Reporting</strong>: Results display the number of records needing attention and provide detailed breakdowns</li>
    </ol>

    <h5>Batch Processing</h5>
    <ul>
        <li>Tools can process thousands of QSOs efficiently</li>
        <li>Progress indicators show real-time processing status</li>
        <li>Results are categorized by fix type and success rate</li>
    </ul>

    <h5>Safety Features</h5>
    <ul>
        <li>Most tools operate in "check" mode first, allowing review before applying fixes</li>
        <li>Detailed logging shows which records would be modified</li>
        <li>Backup recommendations are provided before bulk operations</li>
    </ul>

    <h5>Access and Usage</h5>

    <h5>Location</h5>
    <ul>
        <li>Navigate to <strong>Advanced Logbook</strong> → <strong>DBTools</strong> tab</li>
        <li>Requires appropriate user permissions (typically admin or logbook owner)</li>
    </ul>

    <h5>Interface</h5>
    <ul>
        <li><strong>Left Panel</strong>: Tool selection and action buttons</li>
        <li><strong>Right Panel</strong>: Real-time results and progress information</li>
        <li><strong>Actions</strong>: Check, Fix, or Run buttons depending on tool type</li>
    </ul>

    <h5>Workflow</h5>
    <div class="workflow-step">
        <strong>Step 1</strong>: Use "Check" buttons to identify issues
    </div>
    <div class="workflow-step">
        <strong>Step 2</strong>: Review results and affected QSOs
    </div>
    <div class="workflow-step">
        <strong>Step 3</strong>: Apply fixes using the appropriate action button
    </div>
    <div class="workflow-step">
        <strong>Step 4</strong>: Monitor progress and review final results
    </div>
	<br/>
    <h5>Technical Implementation</h5>

    <h5>Data Sources</h5>
    <ul>
        <li>DXCC entity database from official IARU listings</li>
        <li>Coordinate systems using WGS-84 standard</li>
        <li>Zone definitions from CQ and ITU specifications</li>
        <li>Administrative boundary data for state/province determination</li>
    </ul>

    <h5>Performance Considerations</h5>
    <ul>
        <li>Batch processing optimized for large logbooks</li>
        <li>Memory-efficient database queries</li>
        <li>Progress tracking for long-running operations</li>
    </ul>

    <h5>Best Practices</h5>

    <ol>
        <li><strong>Backup First</strong>: Always create a logbook backup before running bulk fixes</li>
        <li><strong>Test Small</strong>: Run checks on small date ranges first to validate results</li>
        <li><strong>Review Results</strong>: Carefully examine what will be changed before applying fixes</li>
        <li><strong>Monitor Progress</strong>: For large logbooks, allow adequate processing time</li>
        <li><strong>Verify After</strong>: Spot-check results after fixes are applied to ensure accuracy</li>
    </ol>

    <h5>Troubleshooting</h5>

    <h5>Common Issues</h5>
    <ul>
        <li><strong>Memory Limits</strong>: Large logbooks may require increased PHP memory limits</li>
        <li><strong>Timeout Issues</strong>: Long operations may need extended execution time limits</li>
        <li><strong>Coordinate Accuracy</strong>: Some fixes depend on accurate grid square data</li>
    </ul>

    <h5>Error Handling</h5>
    <ul>
        <li>Tools provide detailed error messages for failed operations</li>
        <li>Rollback capabilities for interrupted processes</li>
        <li>Logging of skipped records with reason codes</li>
    </ul>
