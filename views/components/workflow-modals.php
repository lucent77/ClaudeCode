<!-- Complete Step Modal -->
<div id="completeModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideCompleteModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-green-100">
                    <svg class="h-6 w-6 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Complete Step</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">Mark this step as complete and advance to the next step.</p>
                    </div>
                </div>
            </div>
            <form id="completeForm" class="mt-5 space-y-4">
                <input type="hidden" id="completeEntityId" name="entity_id">
                <input type="hidden" id="completeStepCode" name="step_code">
                <div id="machineInputGroup" class="hidden">
                    <label for="machineName" class="form-label">Machine Name <span class="text-red-500">*</span></label>
                    <input type="text" id="machineName" name="machine_name" class="form-input" placeholder="Enter machine name used">
                </div>
                <div>
                    <label for="completeNotes" class="form-label">Notes (optional)</label>
                    <textarea id="completeNotes" name="notes" rows="2" class="form-input" placeholder="Any notes about this step..."></textarea>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="submit" class="w-full btn btn-success sm:col-start-2">Complete Step</button>
                    <button type="button" onclick="hideCompleteModal()" class="mt-3 sm:mt-0 w-full btn btn-secondary sm:col-start-1">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Hold Modal -->
<div id="holdModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideHoldModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Put On Hold</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">This case will be marked as on hold and removed from the active workflow.</p>
                    </div>
                </div>
            </div>
            <form id="holdForm" class="mt-5 space-y-4">
                <input type="hidden" id="holdEntityId" name="entity_id">
                <div>
                    <label for="holdReason" class="form-label">Hold Reason <span class="text-red-500">*</span></label>
                    <textarea id="holdReason" name="reason" rows="3" class="form-input" placeholder="Please provide a reason for putting this case on hold..." required></textarea>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:col-start-2 sm:text-sm">Put On Hold</button>
                    <button type="button" onclick="hideHoldModal()" class="mt-3 sm:mt-0 w-full btn btn-secondary sm:col-start-1">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bulk Hold Modal -->
<div id="bulkHoldModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideBulkHoldModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full sm:p-6">
            <div>
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-yellow-100">
                    <svg class="h-6 w-6 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <div class="mt-3 text-center sm:mt-5">
                    <h3 class="text-lg leading-6 font-medium text-gray-900">Put Selected On Hold</h3>
                    <div class="mt-2">
                        <p class="text-sm text-gray-500">The selected cases will be marked as on hold.</p>
                    </div>
                </div>
            </div>
            <form id="bulkHoldForm" class="mt-5 space-y-4">
                <div>
                    <label for="bulkHoldReason" class="form-label">Hold Reason <span class="text-red-500">*</span></label>
                    <textarea id="bulkHoldReason" name="reason" rows="3" class="form-input" placeholder="Please provide a reason..." required></textarea>
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3 sm:grid-flow-row-dense">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-yellow-600 text-base font-medium text-white hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-yellow-500 sm:col-start-2 sm:text-sm">Put On Hold</button>
                    <button type="button" onclick="hideBulkHoldModal()" class="mt-3 sm:mt-0 w-full btn btn-secondary sm:col-start-1">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Note Tags Modal -->
<div id="noteTagsModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="hideNoteTagsModal()"></div>
        <div class="inline-block align-bottom bg-white rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full sm:p-6">
            <div class="text-center">
                <h3 class="text-lg leading-6 font-medium text-gray-900">Edit Note Tags</h3>
            </div>
            <form id="noteTagsForm" class="mt-5 space-y-4">
                <input type="hidden" id="noteTagsEntityId" name="entity_id">
                <div id="noteTagsContainer" class="space-y-2">
                    <!-- Tags will be populated dynamically -->
                </div>
                <div class="mt-5 sm:mt-6 sm:grid sm:grid-cols-2 sm:gap-3">
                    <button type="submit" class="w-full btn btn-primary sm:col-start-2">Save Tags</button>
                    <button type="button" onclick="hideNoteTagsModal()" class="mt-3 sm:mt-0 w-full btn btn-secondary sm:col-start-1">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
