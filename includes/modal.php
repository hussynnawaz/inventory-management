<?php
// Reusable Bootstrap 5 modal
function modal_markup_html(): string {
    ob_start();
    ?>
    <div id="appModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-0 pb-0">
                    <h5 id="appModalTitle" class="modal-title fw-bold">Notice</h5>
                    <button type="button" class="btn-close" onclick="hideModal()"></button>
                </div>
                <div class="modal-body">
                    <p id="appModalBody" class="text-muted mb-0"></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm px-3" onclick="hideModal()">Close</button>
                </div>
            </div>
        </div>
    </div>
    <div id="confirmModal" class="modal fade" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <div class="modal-header border-0 pb-0">
                    <h5 id="confirmModalTitle" class="modal-title fw-bold">Confirm</h5>
                    <button type="button" class="btn-close" onclick="hideConfirm(false)"></button>
                </div>
                <div class="modal-body">
                    <p id="confirmModalBody" class="text-muted mb-0"></p>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light btn-sm px-3" onclick="hideConfirm(false)">Cancel</button>
                    <button type="button" id="confirmModalOkBtn" class="btn btn-danger btn-sm px-3" onclick="hideConfirm(true)">Confirm</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        var bsModal = null;
        var bsConfirmModal = null;
        var confirmResolve = null;
        function showModal(title, body, type) {
            document.getElementById('appModalTitle').textContent = title;
            var titleEl = document.getElementById('appModalTitle');
            titleEl.className = 'modal-title fw-bold ' + (type === 'success' ? 'text-success' : type === 'error' ? 'text-danger' : '');
            document.getElementById('appModalBody').textContent = body;
            var el = document.getElementById('appModal');
            if (!bsModal) bsModal = new bootstrap.Modal(el);
            bsModal.show();
        }
        function hideModal() { if (bsModal) bsModal.hide(); }
        function confirmModal(title, body, btnLabel) {
            return new Promise(function(resolve) {
                confirmResolve = resolve;
                document.getElementById('confirmModalTitle').textContent = title;
                document.getElementById('confirmModalBody').textContent = body;
                document.getElementById('confirmModalOkBtn').textContent = btnLabel || 'Confirm';
                var el = document.getElementById('confirmModal');
                if (!bsConfirmModal) bsConfirmModal = new bootstrap.Modal(el);
                bsConfirmModal.show();
            });
        }
        function hideConfirm(result) { if (bsConfirmModal) bsConfirmModal.hide(); if (confirmResolve) confirmResolve(result); }
    </script>
    <?php
    return ob_get_clean();
}

function modal_markup(): void { echo modal_markup_html(); }
