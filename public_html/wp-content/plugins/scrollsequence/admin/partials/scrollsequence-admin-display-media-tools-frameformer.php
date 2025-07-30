
<style>
.ssqframeformer-drag-drop-area {
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
  border: 2px dashed #ccc;
  border-radius: 5px;
  padding: 20px;
  text-align: center;
  font-size: 16px;
  color: #666;
  cursor: pointer;
  max-width:350px;
  min-height:150px;
}

.ssqframeformer-drag-drop-area .dashicons {
  font-size: 50px;
  margin-bottom: 50px;
}

.ssqframeformer-drag-drop-area p {
  margin: 0;
  margin-bottom: 2rem;
}

.ssqframeformer-drag-drop-area:hover {
  background-color: #f9f9f9;
}

.ssqframeformer-drag-drop-area.active {
  border-color: #1e8cbe;
  background-color: #f1f1f1;
  color: #1e8cbe;
}

.ssqframeformer-drag-drop-area.active .dashicons {
  color: #1e8cbe;
}

</style>


<?php 
$scrollsequence_licenseid = ( freemius_scrollsequence()->_get_license() ? freemius_scrollsequence()->_get_license()->id : "none" );
$scrollsequence_installid = freemius_scrollsequence()->get_site()->id;
$scrollsequence_year_flk = date( "Y" );
$scrollsequence_month_flk = date( "m" );
$scrollsequence_timestamp = time();
$filekey = $scrollsequence_year_flk . '-' . $scrollsequence_month_flk . '/lic-' . $scrollsequence_licenseid . '-ins-' . $scrollsequence_installid . '-ts-' . $scrollsequence_timestamp;
?>



<h3>
    <?php 
_e( 'Frame Former', 'scrollsequence' );
?>
    <div class="ssqtooltip">
        <span class="dashicons  dashicons-editor-help"></span>
        <span class="ssqtooltiptext">
            <?php 
_e( 'Upload video and the tool will form new frames using AWS Elemental Media Convert Frame Former. Suitable for cases when you have a low framerate video, or want to make your animations smoother.', 'scrollsequence' );
?>
        </span>
    </div>    
</h3>



<section>
    <div class="ssqframeformer-drag-drop-area" style="margin-left:1rem;">
        <div class="dashicons dashicons-upload"></div>
        <p> <?php 
_e( 'Drag and drop your video file here or click on "Choose File" button.', 'scrollsequence' );
?></p>
        <input 
            type="file" 
            id="ssqframeformer-video-file" 
            accept="video/*" 
            style="padding-left:1rem; padding-right:2rem;"
        />
        <div id="ssqframeformer-file-alert-div" style="min-height:2rem; padding-top:1.25rem;"></div>
    </div>
    <p style="margin-left:2rem;" ><i> <span  class="dashicons dashicons-database"></span> 1GB  <span style="margin-left:2rem;" class="dashicons dashicons-video-alt2"></span> 100 <?php 
_e( 'seconds', 'scrollsequence' );
?> </i></p>
    <div style="padding-top:2rem">
        <label for="ssqframeformer-slugname" style="display:block; font-weight:bold; margin-bottom:0.5rem;">
            <?php 
_e( 'File prefix', 'scrollsequence' );
?>
            <div class="ssqtooltip">
                <span class="dashicons  dashicons-editor-help"></span>
                <span class="ssqtooltiptext">
                    <?php 
_e( 'Only charactes &quot;a-z&quot;, &quot;0-9&quot; and hyphen &quot;-&quot; are allowed!', 'scrollsequence' );
?>
                </span>
            </div>    
        </label>
        <input 
            id="ssqframeformer-slugname" 
            type="text" 
            maxlength="30" 
            onkeydown="return /[a-z0-9-]/i.test(event.key)" value="my-scrollsequence" 
            style="margin-left:1rem;"
        ></input>
    </div>



    <div style="padding-top:2rem;margin-left:1rem;">
        <button id="ssqframeformer-submit" class="button button-primary" disabled style="font-size:14px; line-height:unset!important;"  >
            <span class="dashicons dashicons-cloud-upload" ></span>
            <?php 
_e( 'Upload Video', 'scrollsequence' );
?>
        </button>
    </div>

    <?php 
/* RESULTS  START */
?>
    <div id="ssqframeformer-results-wrap" style="visibility:hidden; min-height:500px" >
        <h3> 
            <?php 
_e( 'Output', 'scrollsequence' );
?> 
            <div class="ssqtooltip">
                <span class="dashicons  dashicons-editor-help"></span>
                <span class="ssqtooltiptext">
                    <?php 
_e( 'Source video is converted to 60FPS.', 'scrollsequence' );
?>
                </span>
            </div>    
        </h3>

        <span id="ssqframeformer-upload-progress" style="visibility:hidden;margin-left:1rem;">
            <label for="ssqframeformer-upload-progress-bar" style="margin-right: 10px"><?php 
_e( 'Upload Progress', 'scrollsequence' );
?>: </label>
            <progress id="ssqframeformer-upload-progress-bar" value="0" max="100"> 0% </progress>
        </span>

        <p id="ssqframeformer-processing-message" style="margin-bottom:1rem;margin-left:1rem;"></p>


        <a href id="ssqframeformer-results-button" style="visibility:hidden; margin-bottom:1rem; margin-right:2rem;margin-left:1rem;" class="button button-primary" target="_blank">Download Video </a>
    </div>

    <?php 
/* RESULTS  END */
?>




</section>

<?php 
?>
    <script>
        (function () {
            const submitButton = document.querySelector('#ssqframeformer-submit');
            submitButton.disabled=false; 

            submitButton.addEventListener('click', ()=> { ssqFrameFormerHandleSubmit() } ); 
            function ssqFrameFormerHandleSubmit(){
                if (confirm("Frame Former is available on Business plans only. Do you wish to continue?")) {
                    window.location.href = "<?php 
echo admin_url( 'admin.php?page=scrollsequence-pricing&trial=true' );
?>";
                }
            }
        })();
    </script>
<?php 
?>

