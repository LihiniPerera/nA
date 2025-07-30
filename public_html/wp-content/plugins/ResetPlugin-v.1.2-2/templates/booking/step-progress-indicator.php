<?php
// Get the current step progress data
$step_progress = $wizard->get_step_progress();
$steps = $step_progress['steps'];
?>

<div class="step-progress">
    <div class="step-progress-line"></div>
    
    <?php foreach ($steps as $step_num => $step): ?>
        <div class="step-item <?php echo $step['current'] ? 'current' : ''; ?> <?php echo $step['completed'] ? 'completed' : ''; ?> <?php echo $step['skipped'] ? 'skipped' : ''; ?>">
            <div class="step-circle">
                <?php if ($step['completed']): ?>
                    <span class="checkmark">✓</span>
                <?php elseif ($step['skipped']): ?>
                    <span class="skip-mark">—</span>
                <?php else: ?>
                    <span class="step-number"><?php echo $step_num; ?></span>
                <?php endif; ?>
            </div>
            <div class="step-label"><?php echo esc_html($step['title']); ?></div>
        </div>
    <?php endforeach; ?>
</div>

<style>
.step-progress {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 30px 0;
    position: relative;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.step-progress-line {
    position: absolute;
    top: 20px;
    left: 12.5%;
    right: 12.5%;
    height: 2px;
    background: #e9ecef;
    z-index: 1;
}

.step-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    z-index: 2;
    flex: 1;
    text-align: center;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: #e9ecef;
    border: 2px solid #e9ecef;
    margin-bottom: 8px;
    transition: all 0.3s ease;
    font-weight: bold;
}

.step-item.current .step-circle {
    background: #f9c613;
    border-color: #f9c613;
    color: #000000;
    transform: scale(1.1);
    box-shadow: 0 0 0 3px rgba(249, 198, 19, 0.3);
}

.step-item.completed .step-circle {
    background: #28a745;
    border-color: #28a745;
    color: white;
}

.step-item.skipped .step-circle {
    background: #6c757d;
    border-color: #6c757d;
    color: white;
    opacity: 0.6;
}

.step-label {
    font-size: 14px;
    font-weight: 600;
    color: #6c757d;
    transition: color 0.3s ease;
}

.step-item.current .step-label {
    color: #000000;
}

.step-item.completed .step-label {
    color: #28a745;
}

.step-item.skipped .step-label {
    color: #6c757d;
    opacity: 0.6;
}

.step-progress .checkmark, .step-progress .skip-mark {
    font-size: 18px;
    line-height: 1;
    opacity: 1 !important;
}

.step-progress .step-number {
    font-size: 16px;
    line-height: 1;
}

/* Progress line animation */
.step-progress-line::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    background: linear-gradient(90deg, #f9c613 0%, #ffdd44 100%);
    transition: width 0.5s ease;
    <?php 
    $progress_width = 0;
    $completed_steps = 0;
    foreach ($steps as $step) {
        if ($step['completed']) {
            $completed_steps++;
        }
    }
    $progress_width = ($completed_steps / 3) * 100; // 3 connections between 4 steps
    ?>
    width: <?php echo $progress_width; ?>%;
}

/* Mobile responsiveness */
@media (max-width: 768px) {
    .step-progress {
        margin: 20px 0;
    }
    
    .step-circle {
        width: 35px;
        height: 35px;
    }
    
    .step-label {
        font-size: 12px;
    }
    
    .step-progress-line {
        left: 20%;
        right: 20%;
    }
}

@media (max-width: 480px) {
    .step-progress {
        flex-wrap: wrap;
        justify-content: center;
        gap: 20px;
    }
    
    /* .step-progress-line {
        display: none;
    } */
    
    .step-item {
        flex: 0 0 auto;
        width: auto;
    }
    
    .step-circle {
        width: 30px;
        height: 30px;
    }
    
    .step-label {
        font-size: 11px;
    }
}
</style> 