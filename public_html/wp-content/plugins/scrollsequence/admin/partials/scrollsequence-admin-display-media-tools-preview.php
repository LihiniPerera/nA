


<?php



$length = (int)sanitize_text_field($_GET['length']);
$root = esc_url(sanitize_text_field($_GET['root']));

$inputImages = getInputImages($length, $root);


function getInputImages($length, $root){
    $result = [];
    for ($x = 0; $x < $length; $x++) {
        $result[$x] = $root."-".sprintf('%04d', $x).".jpg";
    };

    return $result;
};

?>
<h1 style="margin-bottom:2rem;">Scrollsequence <?php _e( 'Preview', 'scrollsequence' ); ?> (<span class="dashicons dashicons-images-alt2"></span> <?php echo esc_attr($length); ?> )</h1>
<p style="margin-bottom:5rem;"><?php _e( 'Images will be deleted after 24 hours from our server.', 'scrollsequence' ); ?></p>

<scrollsequence>	
    <style>
        .scrollsequence-pages-wrap {height:100vh}
        .scrollsequence-page {position:absolute;display:none}

        .gsap-marker-scroller-start {z-index:999999!important;} 
    </style>			
    <noscript> 
        <style>
        .scrollsequence-wrap {height:initial!important;}
        .scrollsequence-pages-wrap {height:initial}
        .scrollsequence-page {position:relative;opacity:initial;display:block  ;visibility:initial}	   
        </style> 
    </noscript>

    <div style="position: fixed;z-index:99999;bottom:50px;left: 100px;background:#383131b0;color:white; padding: 0.5rem; display:none" class="ssq-alert-container" id="ssqalert">
    </div>
		
    <style>
        .ssq-center-center{
            position: absolute;
            left: 50%;
            top: 50%;
            transform: translate(-50%, -50%);
            text-shadow: 0 0 8px white;
            text-align: center;
        }
    </style>
				
    <section class="scrollsequence-wrap ssq-wrap-0 " id="ssq-uid-1018-0-1018" style="height: calc(100vh + <?php echo esc_attr((int)$length*25); ?>px); padding: 0px; margin: 0px; width: 100%; max-width: initial; box-sizing: border-box; opacity: 1; visibility: inherit;">
        <div class="scrollsequence-sticky" style="position: -webkit-sticky;position:sticky;top:0;box-sizing: border-box;">
            <canvas class="scrollsequence-canvas" style="position: absolute; opacity: 1; width: 1029px; height: 857px; visibility: inherit;" width="1029" height="857"></canvas>
            <div class="scrollsequence-pages-wrap" style="position:relative; margin:0; padding:0; box-sizing: border-box;">
                <div class="scrollsequence-page" id="ssq-page-0-0" style="box-sizing: border-box; height: 100vh; width: 100%; overflow: hidden !important; display: block;">
                </div>  
            </div>
        </div><!-- .scrollsequence-sticky -->
    </section><!-- .scrollsequence-wrap -->
    <script class="scrollsequence-input-script">
    var ssqInput={};
            ssqInput={
                "debug":[],//["debug","preloaddebug"],
                "show_footer":false,
                "show_sidebar":false,
                "preloadPercentage":"0.12",
                "ssqFyiId":99999,
                "siteUrl":"http:\/\/localhost\/ssq1"}; <?php /*!!!!!! */?>
            ssqInput.sc=[];
            ssqInput.sc[0]={
                "ssqId":99999,
                "bodyBgColor":"",
                "imageOpacity":"1",
                "scrub":0.75,
                "forceFullWidth":0,
                "zIndex":null,
                "triggerStart":"1",
                "triggerEnd":"0",
                "canvasDPR":"performance",
                "page":[{"alignX":{"desktop":"0.5",
                    "mobile":"0.5"},
                "alignY":{"desktop":"0.5",
                    "mobile":"0.5"},
                "imgDur":25,
                "scaleTo":{"desktop":"fit",
                    "mobile":"fit"},
                "animEl":[],
                <?php echo '"imagesFull":["' . implode('", "', $inputImages) . '"]';  ?>,
                "imagesLength":<?php echo esc_attr($length); ?>,
                "animElLength":0,
                "pageSpacer":<?php echo esc_attr((int)$length*25); ?>,
                "pageAbsBegin":0,
                "pageAbsEnd":<?php echo esc_attr((int)$length*25); ?>}], <?php /*!!!!!! */?>
                "pageLength":1,
                "scSpacer":<?php echo esc_attr((int)$length*25); ?>, <?php /*!!!!!! */?>
                "position":"sticky-css"};

    //console.log('ssqInput:',ssqInput)
    </script>
</scrollsequence>

<div style="height:1000px"></div>

