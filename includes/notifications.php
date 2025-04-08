<?php
function showNotification($message, $type = 'success') {
    $icon = $type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';
    $bgColor = $type === 'success' ? 'bg-green-500' : 'bg-red-500';
    $borderColor = $type === 'success' ? 'border-green-600' : 'border-red-600';
    
    return '
    <div class="fixed top-4 right-4 z-50 transform transition-all duration-300 ease-in-out" x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 5000)">
        <div class="' . $bgColor . ' ' . $borderColor . ' border-l-4 p-4 rounded-lg shadow-lg max-w-sm w-full flex items-center space-x-3">
            <div class="flex-shrink-0">
                <i class="fas ' . $icon . ' text-white text-xl"></i>
            </div>
            <div class="flex-1">
                <p class="text-sm text-white font-medium">' . htmlspecialchars($message) . '</p>
            </div>
            <div class="flex-shrink-0">
                <button @click="show = false" class="text-white hover:text-gray-200 focus:outline-none">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        </div>
    </div>';
}
?> 