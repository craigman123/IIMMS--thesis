{{-- resources/views/admin/pages/logs.blade.php --}}

<div class="page" id="page-logs">
    <div class="page-header">
        <h1>Audit <span class="gold">Logs</span></h1>
        <p>System activity and access records.</p>
    </div>
    <div class="panel-card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Target</th>
                        <th>IP Address</th>
                    </tr>
                </thead>
                <tbody id="logTableBody">
                    <tr><td colspan="5" class="empty-cell">No logs available.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
