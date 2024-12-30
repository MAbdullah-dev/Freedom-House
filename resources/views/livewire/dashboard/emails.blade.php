<div class="email py-5 px-4">
    <h3>GUEST EMAILS</h3>

    <!-- Table to display emails -->
    <div class="table-responsive">

        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Email</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($emails as $email)
                    <tr>
                        <td>{{ $email->id }}</td>
                        <td>{{ $email->email }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Export Button -->
    <div class="d-flex justify-content-end mt-4">
        <button wire:click="exportCsv" class="button-primary">Export as CSV</button>
    </div>
</div>
