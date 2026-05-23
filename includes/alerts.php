<?php
/**
 * Alerts System Helper
 * Automatically handles the visual rendering of session toasts for success/error events
 */

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Render dynamic floating alerts container if message is active
if (isset($_SESSION['success_message']) || isset($_SESSION['error_message'])): ?>
    <div class="alert-toast-container">
        
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert-toast toast-success" role="alert">
                <div class="alert-toast-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>
                <div class="alert-toast-body">
                    <?php 
                        echo htmlspecialchars($_SESSION['success_message']); 
                        unset($_SESSION['success_message']); // Prevent repeated rendering
                    ?>
                </div>
                <button type="button" class="alert-toast-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert-toast toast-error" role="alert">
                <div class="alert-toast-icon">
                    <i class="fa-solid fa-circle-exclamation"></i>
                </div>
                <div class="alert-toast-body">
                    <?php 
                        echo htmlspecialchars($_SESSION['error_message']); 
                        unset($_SESSION['error_message']); // Prevent repeated rendering
                    ?>
                </div>
                <button type="button" class="alert-toast-close" aria-label="Close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        <?php endif; ?>

    </div>
<?php endif; ?>
