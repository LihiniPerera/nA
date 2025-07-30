<style>
    .ssq-external-list {
        list-style: none;
        padding: 0;
        display: flex;
        flex-wrap: wrap;
        gap: 20px;
    }

    .ssq-external-item {
        flex: 1;
        padding: 15px;
        border: 1px solid #ddd;
        text-align: center;
    }
</style>

<h3><?php _e( 'External Tools', 'scrollsequence' ); ?></h3>

<ul class="ssq-external-list">
    <li class="ssq-external-item">
        <div style="text-align:center">
            <span class="dashicons dashicons-editor-expand" style="font-size:36px; margin:2rem;"></span>
        </div>
        <a class="ssq-external-link" href="https://replicate.com/lucataco/real-esrgan-video" target="_blank" >
            <?php _e( 'AI Upscale', 'scrollsequence' ); ?>
        </a>
        <p>
            <?php _e( 'Enhance video quality using AI Upscale, provided by Replicate.com. Registration is required.', 'scrollsequence' ); ?>
        </p>
        
    </li>
    <li class="ssq-external-item">
        <div style="text-align:center">
            <span class="dashicons dashicons-format-image" style="font-size:36px; margin:2rem;"></span>
        </div>        
        <a class="ssq-external-link" href="https://imagify.io/optimizer/" target="_blank" >
            <?php _e( 'Image Optimization', 'scrollsequence' ); ?>
        </a>
        <p>
            <?php _e( 'Find the optimal balance between image size and quality. They have a free optimizer available. Powered by Imagify.io', 'scrollsequence' ); ?>
        </p>
        
    </li>
    <li class="ssq-external-item">
        <div style="text-align:center">
            <span class="dashicons dashicons-cover-image" style="font-size:36px; margin:2rem;"></span>
        </div>        
        <a class="ssq-external-link" href="https://docs.topazlabs.com/video-ai/how-to-guide/stabilize-and-remove-motion-blur" target="_blank" >
            <?php _e( 'Remove Motion Blur', 'scrollsequence' ); ?>
        </a>
        <p>
            <?php _e( 'Sharpen still images with AI. Powered by Topazlabs.com. Registration required.', 'scrollsequence' ); ?>
        </p>
        
    </li>
</ul>