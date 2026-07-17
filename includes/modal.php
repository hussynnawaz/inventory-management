<?php
// Reusable modal markup + modal trigger script.
// Call modal_markup() once per page (in layout or page), then
// call show_modal('Title', 'Message') from inline JS to open it.

function modal_markup_html(): string {
    ob_start();
    ?>
    <div id="appModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 p-4">
        <div class="w-full max-w-md rounded-xl bg-white shadow-xl border border-slate-200">
            <div class="px-5 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 id="appModalTitle" class="font-semibold text-slate-800">Notice</h3>
                <button type="button" onclick="hideModal()" class="text-slate-400 hover:text-slate-600">&times;</button>
            </div>
            <div class="px-5 py-4">
                <p id="appModalBody" class="text-sm text-slate-600"></p>
            </div>
            <div class="px-5 py-3 border-t border-slate-200 text-right">
                <button type="button" onclick="hideModal()"
                    class="bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-medium px-4 py-2 rounded-lg">Close</button>
            </div>
        </div>
    </div>
    <script>
        function showModal(title, body, type) {
            document.getElementById('appModalTitle').textContent = title;
            document.getElementById('appModalBody').textContent = body;
            var modal = document.getElementById('appModal');
            var titleEl = document.getElementById('appModalTitle');
            if (type === 'success') { titleEl.className = 'font-semibold text-green-700'; }
            else if (type === 'error') { titleEl.className = 'font-semibold text-red-700'; }
            else { titleEl.className = 'font-semibold text-slate-800'; }
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
        function hideModal() {
            var modal = document.getElementById('appModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
    <?php
    return ob_get_clean();
}

// Print the modal markup directly (for pages that render it inline).
function modal_markup(): void {
    echo modal_markup_html();
}
