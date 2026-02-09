// Detail Modal Handlers for Different Modules

// Asset Detail View
async function viewAssetDetails(assetId) {
    try {
        const response = await fetch(`api/assets.php?id=${assetId}`);
        const asset = await response.json();
        
        if (!asset || asset.error) {
            throw new Error('Asset not found');
        }
        
        // Parse tags if available
        let tagsHtml = 'No tags';
        if (asset.tags && Array.isArray(asset.tags) && asset.tags.length > 0) {
            tagsHtml = asset.tags.map(tag => 
                `<span class="inline-block px-2 py-1 text-xs rounded-full mr-1" style="background-color: ${tag.color}20; color: ${tag.color}; border: 1px solid ${tag.color};">
                    ${tag.name}
                </span>`
            ).join('');
        }
        
        const content = `
            ${createDetailSection('Basic Information', [
                { label: 'Asset Code', value: asset.asset_code },
                { label: 'Asset Name', value: asset.name },
                { label: 'Category', value: asset.category_name || 'Uncategorized' },
                { label: 'Status', value: createStatusBadge(asset.status?.toUpperCase() || 'N/A', 
                    asset.status === 'available' ? 'success' : 
                    asset.status === 'assigned' ? 'warning' : 'info') },
                { label: 'Condition', value: asset.condition_status || 'N/A' },
                { label: 'Location', value: asset.location || 'N/A' }
            ])}
            
            ${createDetailSection('Description & Details', [
                { label: 'Description', value: asset.description || 'No description' }
            ])}
            
            ${createDetailSection('Financial Information', [
                { label: 'Purchase Date', value: asset.purchase_date ? formatDate(asset.purchase_date) : 'N/A' },
                { label: 'Purchase Cost', value: asset.purchase_cost ? '₱ ' + parseFloat(asset.purchase_cost).toLocaleString() : 'N/A' },
                { label: 'Current Value', value: asset.current_value ? '₱ ' + parseFloat(asset.current_value).toLocaleString() : 'N/A' }
            ])}
            
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-3 pb-2 border-b">Tags</h4>
                <div>${tagsHtml}</div>
            </div>
            
            ${asset.qr_code ? `
            <div class="mb-6 text-center">
                <h4 class="text-lg font-semibold text-gray-900 mb-3 pb-2 border-b">QR Code</h4>
                <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(JSON.stringify({asset_id: asset.id, asset_code: asset.asset_code}))}" 
                     alt="QR Code" class="mx-auto border p-2 rounded">
                <p class="text-sm text-gray-500 mt-2">Scan to view asset details</p>
            </div>
            ` : ''}
        `;
        
        const footer = `
            <button onclick="window.open('generate_asset_label.php?asset_id=${asset.id}', '_blank')" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                <i class="fas fa-download mr-2"></i>Download Label
            </button>
            <button onclick="closeDetailModal()" 
                    class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                Close
            </button>
        `;
        
        openDetailModal('Asset Details: ' + asset.asset_code, content, footer);
        
    } catch (error) {
        console.error('Error loading asset details:', error);
        showNotification('Failed to load asset details', 'error');
    }
}

async function viewSupplyDetails(supplyId) {
    try {
        const response = await fetch(`api/supplies.php?id=${supplyId}`);
        const supply = await response.json();
        
        if (!supply || supply.error) {
            throw new Error('Supply not found');
        }

        const allSupplies = []
            .concat(typeof liveSupplies !== 'undefined' && Array.isArray(liveSupplies) ? liveSupplies : [])
            .concat(typeof historicalSupplies !== 'undefined' && Array.isArray(historicalSupplies) ? historicalSupplies : []);

        const localSupply = allSupplies.find(function (s) {
            return String(s.id) === String(supplyId);
        }) || null;
        
        // Determine stock status
        let stockStatus = 'Normal';
        let stockType = 'success';
        if (supply.current_stock <= 0) {
            stockStatus = 'OUT OF STOCK';
            stockType = 'danger';
        } else if (supply.current_stock <= supply.minimum_stock) {
            stockStatus = 'LOW STOCK';
            stockType = 'warning';
        }

        var resolvedLocation = supply.location || supply.storage_location || null;
        if (!resolvedLocation && localSupply) {
            resolvedLocation = localSupply.location || localSupply.storage_location || null;
        }

        function inferStorageLocation(categoryValue) {
            if (!categoryValue) return null;
            var c = String(categoryValue).toLowerCase();
            if (c.indexOf('clinic') !== -1) return 'Clinic Storage';
            if (c.indexOf('library') !== -1) return 'Library Storage';
            if (c.indexOf('event') !== -1) return 'Event Storage';
            if (c.indexOf('osas') !== -1) return 'OSAS Storage';
            return null;
        }

        if (!resolvedLocation) {
            resolvedLocation = inferStorageLocation(supply.category) ||
                (localSupply ? inferStorageLocation(localSupply.category) : null);
        }

        const storageLocation = resolvedLocation || 'Storage location not recorded';

        const supplier = supply.supplier_name ||
            supply.supplier ||
            (localSupply ? (localSupply.supplier_name || localSupply.supplier) : null) ||
            'Supplier not recorded';

        const expirySource = supply.expiry_date || (localSupply ? localSupply.expiry_date : null);
        const expiryLabel = expirySource ? formatDate(expirySource) : 'No expiry date recorded';
        
        const content = `
            ${createDetailSection('Basic Information', [
                { label: 'Item Code', value: supply.item_code },
                { label: 'Supply Name', value: supply.name },
                { label: 'Category', value: supply.category || 'Uncategorized' },
                { label: 'Unit', value: supply.unit || 'pcs' }
            ])}
            
            ${createDetailSection('Stock Information', [
                { label: 'Current Stock', value: supply.current_stock || '0' },
                { label: 'Minimum Stock', value: supply.minimum_stock || '0' },
                { label: 'Stock Status', value: createStatusBadge(stockStatus, stockType) },
                { label: 'Storage Location', value: storageLocation }
            ])}
            
            ${createDetailSection('Financial & Supplier', [
                { label: 'Unit Cost', value: supply.unit_cost ? '₱ ' + parseFloat(supply.unit_cost).toLocaleString() : 'N/A' },
                { label: 'Total Value', value: supply.unit_cost ? '₱ ' + (parseFloat(supply.unit_cost) * supply.current_stock).toLocaleString() : 'N/A' },
                { label: 'Supplier', value: supplier }
            ])}
            
            ${createDetailSection('Additional Information', [
                { label: 'Description', value: supply.description || 'No description' },
                { label: 'Expiry Date', value: expiryLabel }
            ])}
        `;
        
        openDetailModal('Supply Details: ' + supply.name, content);
        
    } catch (error) {
        console.error('Error loading supply details:', error);
        showNotification('Failed to load supply details', 'error');
    }
}

// Damaged Item Detail View
async function viewDamagedItemDetails(itemId) {
    try {
        const response = await fetch(`api/damaged_items.php?action=details&id=${itemId}`);
        const result = await response.json();
        
        if (!result.success || !result.data) {
            throw new Error('Damaged item not found');
        }
        
        const item = result.data;
        
        // Parse photos if available
        let photosHtml = '<p class="text-gray-500">No photos uploaded</p>';
        if (item.damage_photos) {
            try {
                const photos = JSON.parse(item.damage_photos);
                if (Array.isArray(photos) && photos.length > 0) {
                    photosHtml = '<div class="grid grid-cols-2 md:grid-cols-3 gap-3">';
                    photos.forEach(photo => {
                        photosHtml += `
                            <div class="relative group">
                                <img src="${photo}" alt="Damage Photo" 
                                     class="w-full h-32 object-cover rounded border cursor-pointer hover:opacity-75 transition"
                                     onclick="window.open('${photo}', '_blank')">
                                <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                                    <i class="fas fa-search-plus text-white text-2xl"></i>
                                </div>
                            </div>
                        `;
                    });
                    photosHtml += '</div>';
                }
            } catch (e) {
                console.error('Error parsing photos:', e);
            }
        }
        
        const severityColors = {
            'minor': 'success',
            'moderate': 'warning',
            'major': 'danger',
            'critical': 'danger'
        };
        
        const content = `
            ${createDetailSection('Asset Information', [
                { label: 'Asset Code', value: item.asset_code },
                { label: 'Asset Name', value: item.asset_name || 'N/A' },
                { label: 'Category', value: item.category || 'N/A' },
                { label: 'Location', value: item.current_location || item.asset_location || 'N/A' }
            ])}
            
            ${createDetailSection('Damage Information', [
                { label: 'Damage Type', value: item.damage_type?.replace(/_/g, ' ').toUpperCase() || 'N/A' },
                { label: 'Severity Level', value: createStatusBadge(item.severity_level?.toUpperCase() || 'N/A', severityColors[item.severity_level] || 'default') },
                { label: 'Damage Date', value: formatDate(item.damage_date) },
                { label: 'Reported By', value: item.reported_by || 'N/A' },
                { label: 'Report Date', value: formatDate(item.created_at) }
            ])}
            
            ${createDetailSection('Financial Impact', [
                { label: 'Estimated Repair Cost', value: item.estimated_repair_cost ? '₱ ' + parseFloat(item.estimated_repair_cost).toLocaleString() : 'N/A' },
                { label: 'Asset Purchase Cost', value: item.purchase_cost ? '₱ ' + parseFloat(item.purchase_cost).toLocaleString() : 'N/A' },
                { label: 'Current Asset Value', value: item.current_value ? '₱ ' + parseFloat(item.current_value).toLocaleString() : 'N/A' }
            ])}
            
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-3 pb-2 border-b">Damage Description</h4>
                <p class="text-gray-700">${item.damage_description || 'No description provided'}</p>
            </div>
            
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-3 pb-2 border-b">Evidence Photos</h4>
                ${photosHtml}
            </div>
        `;
        
        const footer = `
            <button onclick="window.open('generate_incident_pdf.php?id=${item.id}', '_blank')" 
                    class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition duration-200">
                <i class="fas fa-file-pdf mr-2"></i>Download Report
            </button>
            <button onclick="closeDetailModal()" 
                    class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                Close
            </button>
        `;
        
        openDetailModal('Incident Report: ' + item.asset_code, content, footer);
        
    } catch (error) {
        console.error('Error loading damaged item details:', error);
        showNotification('Failed to load incident details', 'error');
    }
}

// Maintenance Detail View
async function viewMaintenanceDetails(maintenanceId) {
    try {
        const response = await fetch(`api/maintenance.php?action=details&id=${maintenanceId}`);
        const result = await response.json();
        
        if (!result.maintenance) {
            throw new Error('Maintenance task not found');
        }
        
        const task = result.maintenance;
        
        const priorityColors = {
            'low': 'success',
            'medium': 'warning',
            'high': 'danger',
            'urgent': 'danger'
        };
        
        const statusColors = {
            'scheduled': 'info',
            'in_progress': 'warning',
            'completed': 'success',
            'cancelled': 'default'
        };
        
        let costVariance = '';
        if (task.actual_cost && task.estimated_cost) {
            const variance = parseFloat(task.actual_cost) - parseFloat(task.estimated_cost);
            const varianceColor = variance > 0 ? 'text-red-600' : 'text-green-600';
            costVariance = `<span class="${varianceColor}">₱ ${Math.abs(variance).toLocaleString()} ${variance > 0 ? '(Over)' : '(Under)'}</span>`;
        }
        
        const content = `
            ${createDetailSection('Asset Information', [
                { label: 'Asset Code', value: task.asset_code },
                { label: 'Asset Name', value: task.asset_name || 'N/A' },
                { label: 'Category', value: task.category || 'N/A' },
                { label: 'Location', value: task.asset_location || 'N/A' }
            ])}
            
            ${createDetailSection('Maintenance Details', [
                { label: 'Maintenance Type', value: task.maintenance_type?.replace(/_/g, ' ').toUpperCase() || 'N/A' },
                { label: 'Priority', value: createStatusBadge(task.priority?.toUpperCase() || 'N/A', priorityColors[task.priority] || 'default') },
                { label: 'Status', value: createStatusBadge(task.status?.toUpperCase() || 'N/A', statusColors[task.status] || 'default') },
                { label: 'Scheduled Date', value: formatDate(task.scheduled_date) },
                { label: 'Completed Date', value: task.completed_date ? formatDate(task.completed_date) : 'Not completed' },
                { label: 'Assigned Technician', value: task.assigned_technician || 'Not assigned' }
            ])}
            
            ${createDetailSection('Cost Information', [
                { label: 'Estimated Cost', value: task.estimated_cost ? '₱ ' + parseFloat(task.estimated_cost).toLocaleString() : 'N/A' },
                { label: 'Actual Cost', value: task.actual_cost ? '₱ ' + parseFloat(task.actual_cost).toLocaleString() : 'Not completed' },
                { label: 'Variance', value: costVariance || 'N/A' }
            ])}
            
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-3 pb-2 border-b">Description</h4>
                <p class="text-gray-700">${task.description || 'No description provided'}</p>
            </div>
            
            ${task.notes ? `
            <div class="mb-6">
                <h4 class="text-lg font-semibold text-gray-900 mb-3 pb-2 border-b">Additional Notes</h4>
                <p class="text-gray-700">${task.notes}</p>
            </div>
            ` : ''}
        `;
        
        const footer = `
            <button onclick="window.open('generate_maintenance_pdf.php?id=${task.id}', '_blank')" 
                    class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-200">
                <i class="fas fa-file-pdf mr-2"></i>Download Report
            </button>
            <button onclick="closeDetailModal()" 
                    class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 transition duration-200">
                Close
            </button>
        `;
        
        openDetailModal('Maintenance Task: ' + task.asset_code, content, footer);
        
    } catch (error) {
        console.error('Error loading maintenance details:', error);
        showNotification('Failed to load maintenance details', 'error');
    }
}

// Helper function to format dates
function formatDate(dateString) {
    if (!dateString) return 'N/A';
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}
