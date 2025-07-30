
<style>
.ssqupscale-drag-drop-area {
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

.ssqupscale-drag-drop-area .dashicons {
  font-size: 50px;
  margin-bottom: 50px;
}

.ssqupscale-drag-drop-area p {
  margin: 0;
  margin-bottom: 2rem;
}

.ssqupscale-drag-drop-area:hover {
  background-color: #f9f9f9;
}

.ssqupscale-drag-drop-area.active {
  border-color: #1e8cbe;
  background-color: #f1f1f1;
  color: #1e8cbe;
}

.ssqupscale-drag-drop-area.active .dashicons {
  color: #1e8cbe;
}

</style>



<?php 
$licenseid = ( freemius_scrollsequence()->_get_license() ? freemius_scrollsequence()->_get_license()->id : "none" );
$installid = freemius_scrollsequence()->get_site()->id;
$year_flk = date( "Y" );
$month_flk = date( "m" );
$timestamp = time();
$filekey = $year_flk . '-' . $month_flk . '/lic-' . $licenseid . '-ins-' . $installid . '-ts-' . $timestamp;
?>



<h3>AI Upscale</h3>
<p>Upload video and the tool resize the input video to 2K resolution.</p>
<p>Suitable for cases when you have a low quality video. </p>
<p></p>




<?php 
/*
<div>
    <p>Pro? <?php echo freemius_scrollsequence()->is_plan_or_trial__premium_only('PRO', true) ? '<b>pro</b>' :'nopro' ?> </p>
    <p>BUSINESS? <?php echo freemius_scrollsequence()->is_plan_or_trial__premium_only('BUSINESS', true) ? '<b>business</b>' :'nobusiness' ?> </p>
    <p>License id: <?php echo $licenseid; ?> </p>
    <p>install id: <?php echo $installid; ?> </p>
    <p>filekey: <?php echo $filekey; ?> </p>
</div>
*/
?>


<section>
    <div class="ssqupscale-drag-drop-area">
        <div class="dashicons dashicons-upload"></div>
        <p>Drag and drop your <strong>video</strong> file here or click to select a file.</p>
        <input 
            type="file" 
            id="ssqupscale-video-file" 
            accept="video/*" 
            style="padding-left:1rem; padding-right:2rem;"
        />
        <div id="ssqupscale-file-alert-div" style="min-height:2rem; padding-top:1.25rem;"></div>
    </div>
    <p><i>1000 MB file size limit. First 100 seconds.</i></p>
    <div style="padding-top:2rem">
        <label for="ssqupscale-slugname" style="display:block; font-weight:bold">File prefix</label>
        <input 
            id="ssqupscale-slugname" 
            type="text" 
            maxlength="30" 
            onkeydown="return /[a-z0-9-]/i.test(event.key)" value="scrollsequence-upscale" 
        ></input>
        <i>Only charactes "a-z", "0-9" and hyphen "-" are allowed! </i>
    </div>



    <div style="padding-top:2rem">
        <input 
            type="submit" 
            name="ssqupscale-submit" 
            id="ssqupscale-submit" 
            class="button button-primary" 
            disabled 
            value="Upload Video" 
        >
    </div>

    <?php 
/* RESULTS  START */
?>
    <div id="ssqupscale-results-wrap" style="visibility:hidden; min-height:500px" >
        <h3> Output </h3>

        <span id="ssqupscale-upload-progress" style="visibility:hidden;">
            <label for="ssqupscale-upload-progress-bar" style="margin-right: 10px">Uploading progress:</label>
            <progress id="ssqupscale-upload-progress-bar" value="0" max="100"> 0% </progress>
        </span>

        <p id="ssqupscale-processing-message" style="margin-bottom:1rem;"></p>


        <a href id="ssqupscale-results-button" style="visibility:hidden; margin-bottom:1rem; margin-right:2rem;" class="button button-primary" target="_blank">Download Video </a>
    </div>

    <?php 
/* RESULTS  END */
?>




</section>

<?php 
?>
    <script>
        (function () {
            const submitButton = document.querySelector('#ssqupscale-submit');
            submitButton.disabled=false; 

            submitButton.addEventListener('click', ()=> { ssqupscaleHandleSubmit() } ); 
            function ssqupscaleHandleSubmit(){
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

