<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Offline Raffle System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Arial', sans-serif;
            background: #f5f5f5;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 30px;
        }

        .header h1 {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }

        .header .back-link {
            color: white;
            text-decoration: none;
            font-size: 1.1rem;
            opacity: 0.9;
        }

        .header .back-link:hover {
            opacity: 1;
        }

        .section {
            background: white;
            border-radius: 10px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .section-title {
            font-size: 1.8rem;
            margin-bottom: 20px;
            color: #667eea;
            border-bottom: 2px solid #667eea;
            padding-bottom: 10px;
        }

        /* Prize List Table */
        .prize-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .prize-table th,
        .prize-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }

        .prize-table th {
            background: #667eea;
            color: white;
            font-weight: bold;
        }

        .prize-table tr:hover {
            background: #f8f9fa;
        }

        .color-box {
            display: inline-block;
            width: 40px;
            height: 25px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: bold;
        }

        .status-active {
            background: #10B981;
            color: white;
        }

        .status-inactive {
            background: #EF4444;
            color: white;
        }

        /* Buttons */
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-right: 5px;
        }

        .btn-primary {
            background: #667eea;
            color: white;
        }

        .btn-primary:hover {
            background: #5568d3;
        }

        .btn-success {
            background: #10B981;
            color: white;
        }

        .btn-success:hover {
            background: #059669;
        }

        .btn-danger {
            background: #EF4444;
            color: white;
        }

        .btn-danger:hover {
            background: #DC2626;
        }

        .btn-small {
            padding: 6px 12px;
            font-size: 0.9rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            margin-bottom: 8px;
            color: #555;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 1rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }

        .modal.active {
            display: flex;
        }

        .modal-content {
            background: white;
            border-radius: 10px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 20px;
            color: #667eea;
        }

        .modal-footer {
            margin-top: 20px;
            text-align: right;
        }

        /* Alert */
        .alert {
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 20px;
            display: none;
        }

        .alert.show {
            display: block;
        }

        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }

        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
        }

        .alert-warning {
            background: #FEF3C7;
            color: #92400E;
            border-left: 4px solid #F59E0B;
        }

        /* Probability Summary */
        .probability-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .probability-summary .total {
            font-size: 1.3rem;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .probability-summary .status {
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            font-weight: bold;
        }

        .probability-valid {
            background: #D1FAE5;
            color: #065F46;
        }

        .probability-invalid {
            background: #FEE2E2;
            color: #991B1B;
        }

        /* Loading */
        .loading {
            text-align: center;
            padding: 40px;
            color: #999;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🎰 Admin Panel</h1>
            <a href="../" class="back-link">← Back to Drawing Page</a>
        </div>

        <!-- Alert Messages -->
        <div id="alertSuccess" class="alert alert-success"></div>
        <div id="alertError" class="alert alert-error"></div>
        <div id="alertWarning" class="alert alert-warning"></div>

        <!-- Prize Management Section -->
        <div class="section">
            <h2 class="section-title">Prize Management</h2>

            <!-- Probability Summary -->
            <div class="probability-summary">
                <div class="total">
                    Total Probability (Active Prizes): <span id="totalProbability">0</span>%
                </div>
                <div id="probabilityStatus" class="status"></div>
            </div>

            <!-- Add Prize Button -->
            <button class="btn btn-primary" onclick="openAddPrizeModal()">
                + Add New Prize
            </button>

            <!-- Prize List -->
            <div id="prizeListContainer" class="loading">Loading prizes...</div>
        </div>

        <!-- Special Prize Settings Section -->
        <div class="section">
            <h2 class="section-title">Special Prize Settings</h2>
            <p style="margin-bottom: 20px; color: #666;">
                Configure milestone-based special prizes. When the drawing count reaches the milestone,
                the selected special prize will be automatically awarded.
            </p>

            <form id="specialPrizeForm" onsubmit="saveSpecialPrizeSettings(event)">
                <div class="form-group">
                    <label>
                        <input type="checkbox" id="specialPrizeActive" style="width: auto; margin-right: 8px;">
                        Enable Special Prize Feature
                    </label>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="specialPrizeId">Special Prize *</label>
                        <select id="specialPrizeId" required>
                            <option value="">Select a prize...</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="milestoneCount">Milestone Count *</label>
                        <input type="number" id="milestoneCount" min="1" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="currentCount">Current Count</label>
                    <input type="number" id="currentCount" min="0" required>
                </div>

                <button type="submit" class="btn btn-success">Save Settings</button>
            </form>
        </div>
    </div>

    <!-- Add/Edit Prize Modal -->
    <div id="prizeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header" id="modalTitle">Add New Prize</div>

            <form id="prizeForm" onsubmit="savePrize(event)">
                <input type="hidden" id="prizeId">

                <div class="form-group">
                    <label for="prizeName">Prize Name *</label>
                    <input type="text" id="prizeName" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="prizeProbability">Probability (%) *</label>
                        <input type="number" id="prizeProbability" step="0.01" min="0" max="100" required>
                    </div>

                    <div class="form-group">
                        <label for="prizeColor">Color *</label>
                        <input type="color" id="prizeColor" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="prizeStock">Stock (-1 for unlimited) *</label>
                        <input type="number" id="prizeStock" required>
                    </div>

                    <div class="form-group">
                        <label for="prizeActive">Status *</label>
                        <select id="prizeActive" required>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" onclick="closePrizeModal()">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Prize</button>
                </div>
            </form>
        </div>
    </div>

    <script src="admin.js"></script>
    <script>
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadPrizes();
            loadSpecialPrizeSettings();
        });
    </script>
</body>
</html>
