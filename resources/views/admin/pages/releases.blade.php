{{-- resources/views/admin/pages/releases.blade.php --}}

<div class="page" id="page-releases">
    <div class="page-header">
        <h1>Scheduled <span class="gold">Releases</span></h1>
        <p>Upcoming and past inmate releases.</p>
    </div>
    <div class="panel-card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Inmate</th>
                        <th>Cell</th>
                        <th>Release Date</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="releaseTableBody">
                    <tr><td colspan="6" class="empty-cell">No releases scheduled.</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
