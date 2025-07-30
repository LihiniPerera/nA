
<style>
/* Tooltip container */
.ssqtooltip {
  position: relative;
  display: inline-block;
  color:gray;
}

/* Tooltip text */
.ssqtooltip .ssqtooltiptext {
  visibility: hidden;
  width: 360px;
  background-color: #535151;
  color: #fff;
  text-align: center;
  padding: 10px;
  border-radius: 6px;

  position:absolute;
  top: -5px;
  left: 105%;
}

/* Show the tooltip text when you mouse over the tooltip container */
.ssqtooltip:hover .ssqtooltiptext {
  visibility: visible;
}


.ssqconvert-drag-drop-area {
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

.ssqconvert-drag-drop-area .dashicons {
  font-size: 50px;
  margin-bottom: 50px;
}

.ssqconvert-drag-drop-area p {
  margin: 0;
  margin-bottom: 2rem;
}

.ssqconvert-drag-drop-area:hover {
  background-color: #f9f9f9;
}

.ssqconvert-drag-drop-area.active {
  border-color: #1e8cbe;
  background-color: #f1f1f1;
  color: #1e8cbe;
}

.ssqconvert-drag-drop-area.active .dashicons {
  color: #1e8cbe;
}


.ssq-add-to-wp-gallery{
    border: 3px solid transparent;
}

.ssq-wp-media-import-complete{
    border: 3px solid green;
}

</style>



<?php 
$scrollsequence_licenseid = ( freemius_scrollsequence()->_get_license() ? freemius_scrollsequence()->_get_license()->id : "none" );
$scrollsequence_installid = freemius_scrollsequence()->get_site()->id;
$scrollsequence_year_flk = date( "Y" );
$scrollsequence_month_flk = date( "m" );
$scrollsequence_timestamp = time();
$scrollsequence_filekey = $scrollsequence_year_flk . '-' . $scrollsequence_month_flk . '/lic-' . $scrollsequence_licenseid . '-ins-' . $scrollsequence_installid . '-ts-' . $scrollsequence_timestamp;
?>



<h3>
    <?php 
_e( 'Video to Image Sequence Convertor', 'scrollsequence' );
?>
    <div class="ssqtooltip">
        <span class="dashicons  dashicons-editor-help"></span>
        <span class="ssqtooltiptext">
            <?php 
_e( 'Upload video and the tool will convert it to sequence of images and allow you to preview the Scrollsequence. Once you are happy, you can download the .zip file and add the media to WordPress gallery.', 'scrollsequence' );
?>
            <br/>
        </span>
    </div>
</h3>

<section>
    <div class="ssqconvert-drag-drop-area" style="margin-left:1rem;">
        <div class="dashicons dashicons-upload"></div>
        <p>
            <?php 
_e( 'Drag and drop your video file here or click on "Choose File" button.', 'scrollsequence' );
?>
        </p>
        <input 
            type="file" 
            id="ssqconvert-video-file" 
            accept="video/*" 
            style="padding-left:1rem; padding-right:2rem;"
        />
        <div id="ssqconvert-file-alert-div" style="min-height:2rem; padding-top:1.25rem;"></div>
    </div>
    <p style="margin-left:2rem;" ><i> <span  class="dashicons dashicons-database"></span> 1GB <span style="margin-left:2rem;" class="dashicons dashicons-images-alt"></span> 2000  </i></p>
    
    <div style="padding-top:2rem;">
        <label for="ssqconvert-slugname" style="display:block; font-weight:bold; margin-bottom:0.5rem;">File prefix
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
            id="ssqconvert-slugname" 
            type="text" 
            maxlength="30" 
            onkeydown="return /[a-z0-9-]/i.test(event.key)" value="my-scrollsequence" 
            style="margin-left:1rem;"
        ></input>

    </div>

    <div style="padding-top:2rem">
        <label style="display:block; font-weight:bold;margin-bottom:0.5rem;"> 
            <?php 
_e( 'Quality', 'scrollsequence' );
?> 
            <div class="ssqtooltip">
                <span class="dashicons  dashicons-editor-help"></span>
                <span class="ssqtooltiptext">
                    <?php 
_e( 'Choose between Aggressive compression for small file size, optimal or no compression if you want to use your own tools to bring the file size down.', 'scrollsequence' );
?> 
                </span>
            </div>
        </label>
        
        <input type="radio" id="ssqconvert-quality-radio-aggresive" name="ssqconvert_quality_radio" value="aggressive" style="margin-left:1rem;">
        <label for="ssqconvert-quality-radio-aggresive">
            <?php 
_e( 'Aggressive compression (lossy)', 'scrollsequence' );
?> 
        </label><br>

        <input type="radio" id="ssqconvert-quality-radio-optimal" name="ssqconvert_quality_radio" value="optimal" checked style="margin-left:1rem;">
        <label for="ssqconvert-quality-radio-optimal" >
            <?php 
_e( 'Optimal compression (recommended)', 'scrollsequence' );
?> 
        </label><br>

        <input type="radio" id="ssqconvert-quality-radio-original" name="ssqconvert_quality_radio" value="original" style="margin-left:1rem;">
        <label for="ssqconvert-quality-radio-original">
            <?php 
_e( 'Original (no compression)', 'scrollsequence' );
?> 
        </label>
        <br/>
    </div>

    <div style="padding-top:2rem">
        <label style="display:block; font-weight:bold; margin-bottom:0.5rem;">
            <?php 
_e( 'Maximum Resolution', 'scrollsequence' );
?> 
            <div class="ssqtooltip">
                <span class="dashicons  dashicons-editor-help"></span>
                <span class="ssqtooltiptext">
                    <?php 
_e( 'Choose if you want to downscale a large resolution video to a reasonable resolution. We recommend using images no larger than 1920px for Scrollsequence.', 'scrollsequence' );
?> 
                </span>
            </div>
        </label>
        
        <input type="radio" id="ssqconvert-downscale-radio-true" name="ssqconvert_downscale_radio" value="true" checked style="margin-left:1rem;">
        <label for="ssqconvert-downscale-radio-true">
            <?php 
_e( 'Downsize to fit inside 1920x1080px rectangle (recommended)', 'scrollsequence' );
?> 
        </label><br>

        <input type="radio" id="ssqconvert-downscale-radio-false" name="ssqconvert_downscale_radio" value="false" style="margin-left:1rem;" >
        <label for="ssqconvert-downscale-radio-false" >
            <?php 
_e( 'No resize', 'scrollsequence' );
?> 
        </label><br>

        <br/>
    </div>    



    <div style="padding-top:2rem;  margin-left:1rem">
        <button id="ssqconvert-submit" class="button button-primary" disabled  style="font-size:14px; line-height:unset!important;" >
            <span class="dashicons dashicons-cloud-upload"></span>
            <?php 
_e( 'Upload & Convert Video', 'scrollsequence' );
?>
        </button>
    </div>



    <?php 
/* RESULTS 1 - PROGRESS */
?>
    <div id="ssqconvert-r1-wrap" style="visibility:hidden;" >
        <h3> <?php 
_e( 'Job Status', 'scrollsequence' );
?>  </h3>
        <label for="ssqconvert-r1-progress-bar" style="margin-right: 10px; margin-left:1rem">
            <?php 
_e( 'Upload Progress', 'scrollsequence' );
?>: 
        </label>
        <progress id="ssqconvert-r1-progress-bar" value="0" max="100"></progress>
        <p id="ssqconvert-r1-processing-message" style="margin-bottom:1rem;  margin-left:1rem">
        </p>
    </div>
    <?php 
/* RESULTS 2 - OUTPUT */
?>
    <div id="ssqconvert-r2-wrap" style="visibility:hidden; min-height:200px" >
        <h3> <?php 
_e( 'Output', 'scrollsequence' );
?>  </h3>
        <p style="margin-left:1rem">
            <?php 
_e( 'All Images', 'scrollsequence' );
?> (max 2000 <span class="dashicons dashicons-images-alt" style="font-size:14px; line-height:unset!important;"></span> )
        </p>
        <div  style="display:block; margin-left:1rem">
            <a href id="ssqconvert-r2-download-all-images" style="margin-bottom:1rem; margin-right:2rem;" class="button button-primary" >
                <?php 
_e( 'Download all files', 'scrollsequence' );
?>
                <span class="dashicons dashicons-images-alt" style="font-size:14px; line-height:unset!important;"></span>
                <span class="ssqconvert-fullcount">0</span>
            </a>
        </div>
        <p style="margin-left:1rem" >
            <?php 
_e( 'Preview Images', 'scrollsequence' );
?>  (max 500 <span class="dashicons dashicons-images-alt" style="font-size:14px; line-height:unset!important;"></span> )
        </p>
        <div id="ssqconvert-r2-images" style="display:block; height:400px; overflow-y: scroll; border: 1px solid #c3c4c7; padding:1rem;margin-bottom:2rem; margin-left:1rem"></div>

        <a href id="ssqconvert-r2-button-preview-images" style="margin-bottom:1rem; margin-right:2rem;  margin-left:1rem" class="button " target="_blank" >
            <?php 
_e( 'Preview Scrollsequence', 'scrollsequence' );
?>
            <span class="dashicons dashicons-images-alt" style="font-size:14px; line-height:unset!important;"></span>
            <span class="ssqconvert-previewcount">0</span>
        </a>
        <button id="ssqconvert-r2-button-add-to-wp-media-gallery" style="margin-bottom:1rem;  margin-left:1rem" class="button " >
            <?php 
_e( 'Import to WP Media Gallery', 'scrollsequence' );
?>
            <span class="dashicons dashicons-images-alt" style="font-size:14px; line-height:unset!important;"></span>
            <span class="ssqconvert-previewcount">0</span>
        </button>
    </div>

    <?php 
/* RESULTS 3 - ADD TO WP MEDIA GALLERY */
?>
    <div id="ssqconvert-r3-wrap" style="visibility:hidden; min-height:200px" >
        <label for="ssqconvert-wp-media-upload-progress" style="margin-right: 10px;  margin-left:1rem">
            <?php 
_e( 'Importing to WP Media Gallery', 'scrollsequence' );
?>: 
        </label>
        <progress id="ssqconvert-wp-media-upload-progress" value="0" max="100"></progress>
        <p id="ssqconvert-wp-media-upload-status" style=" margin-bottom:4rem;  margin-left:1rem" >Starting Import</p>

        <button id="add_media_to_scrollsequence" class="button button-primary">Save as Scrollsequence</button>
        <br/><br/>
        <div id="add_media_to_scrollsequence_result"></div>
    </div>



    <?php 
/*
    Tooltip:                <span class="dashicons dashicons-editor-help"></span>
    Images:                 <span style="margin-left:2rem;" class="dashicons dashicons-images-alt"></span>
    WP Media Gallery:       <span class="dashicons dashicons-admin-media"></span>
    Preview:                <span class="dashicons dashicons-welcome-view-site"></span>
    Zip (archive)           <span class="dashicons dashicons-media-archive"></span>
    Store: (buy)            <span class="dashicons dashicons-store"></span>
*/
?>




</section>

<?php 
?>
    <script>
        (function () {
            const submitButton = document.querySelector('#ssqconvert-submit');
            submitButton.disabled=false; 

            submitButton.addEventListener('click', ()=> { ssqConvertHandleFormSubmit() } ); 
            function ssqConvertHandleFormSubmit(){
                if (confirm("Video Convert is available on Business plans only. Do you wish to continue?")) {
                    window.location.href = "<?php 
echo admin_url( 'admin.php?page=scrollsequence-pricing&trial=true' );
?>";
                }
            }
        })();
    </script>
<?php 
?>

