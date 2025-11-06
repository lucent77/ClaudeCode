    </main>

    <!-- Footer -->
    <footer class="bg-white border-t mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="text-center text-gray-500 text-sm">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
                <p class="mt-1">Version <?php echo APP_VERSION; ?></p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script>
        // Auto-hide flash messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('[role="alert"]');
            alerts.forEach(alert => {
                alert.style.transition = 'opacity 0.5s';
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 500);
            });
        }, 5000);

        // Confirmation for delete actions
        function confirmDelete(message = 'Are you sure you want to delete this item?') {
            return confirm(message);
        }

        // AJAX helper function
        function ajaxRequest(url, method = 'GET', data = null, callback = null) {
            const xhr = new XMLHttpRequest();
            xhr.open(method, url, true);

            if (method === 'POST') {
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
            }

            xhr.onload = function() {
                if (xhr.status >= 200 && xhr.status < 300) {
                    if (callback) {
                        try {
                            const response = JSON.parse(xhr.responseText);
                            callback(response);
                        } catch (e) {
                            callback(xhr.responseText);
                        }
                    }
                } else {
                    console.error('Request failed:', xhr.statusText);
                }
            };

            xhr.onerror = function() {
                console.error('Request failed');
            };

            xhr.send(data);
        }

        // Auto-complete for product search
        function setupProductAutocomplete(inputId, suggestionsId) {
            const input = document.getElementById(inputId);
            const suggestions = document.getElementById(suggestionsId);

            if (!input || !suggestions) return;

            input.addEventListener('input', function() {
                const query = this.value;

                if (query.length < 2) {
                    suggestions.innerHTML = '';
                    suggestions.classList.add('hidden');
                    return;
                }

                ajaxRequest('/purchase-system/api/search-products.php?q=' + encodeURIComponent(query), 'GET', null, function(response) {
                    if (response.success && response.products.length > 0) {
                        let html = '';
                        response.products.forEach(product => {
                            html += `<div class="p-2 hover:bg-gray-100 cursor-pointer border-b" onclick="selectProduct(${product.id}, '${product.product_name}', ${product.default_vendor_id}, '${product.avg_price}')">
                                <div class="font-medium">${product.product_name}</div>
                                <div class="text-xs text-gray-500">Avg Price: $${product.avg_price}</div>
                            </div>`;
                        });
                        suggestions.innerHTML = html;
                        suggestions.classList.remove('hidden');
                    } else {
                        suggestions.innerHTML = '';
                        suggestions.classList.add('hidden');
                    }
                });
            });

            // Close suggestions when clicking outside
            document.addEventListener('click', function(e) {
                if (e.target !== input) {
                    suggestions.classList.add('hidden');
                }
            });
        }

        function selectProduct(productId, productName, vendorId, avgPrice) {
            document.getElementById('product_name').value = productName;
            document.getElementById('product_suggestions').classList.add('hidden');

            // If vendor select exists, set it
            const vendorSelect = document.getElementById('vendor_id');
            if (vendorSelect && vendorId) {
                vendorSelect.value = vendorId;
            }

            // If price field exists, set it
            const priceField = document.getElementById('price');
            if (priceField && avgPrice) {
                priceField.value = avgPrice;
            }
        }

        // Initialize autocomplete on page load
        document.addEventListener('DOMContentLoaded', function() {
            setupProductAutocomplete('product_name', 'product_suggestions');
        });
    </script>

</body>
</html>
