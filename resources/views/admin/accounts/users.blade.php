{{-- resources/views/admin/pages/users.blade.php --}}

<div class="page" id="page-users">
    <div class="page-header">
        <h1>User <span class="gold">Accounts</span></h1>
        <p>Manage authorized system users.</p>
    </div>
    <div class="panel-card">
        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Badge</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Access</th>
                        <th>Joined</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                    <tr><td colspan="6" class="empty-cell">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
