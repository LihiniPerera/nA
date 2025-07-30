<?php 
//$SCROLLSEQUENCEAPIROOT = "http://local___host/ssq1/wp-json/ssq-media-tools/v1";
$SCROLLSEQUENCEAPIROOT = "https://scrollsequence.com/wp-json/ssq-media-tools/v1";
?>


<style>

.tabvertcontainer {
	display:flex;
}

/* Style the tab */
.tabvert {
  width: 250px;
  height: 300px;
}

/* Style the buttons inside the tab */
.tabvert button {
  display: block;
  background-color: inherit;
  color: #50575e;
  padding: 22px 16px;
  width: 100%;
  border: none;
  outline: none;
  text-align: left;
  cursor: pointer;
  transition: 0.3s;
  font-size: 14px;
}

/* Change background color of buttons on hover */
.tabvert button:hover {
  background-color: #ddd;
}

/* Create an active/current "tab button" class */
.tabvert button.active {
  background-color: #fff;
  font-weight: bold;
  color:black;
}

/* Style the tab content */
.tabvertcontent {

  padding: 0px 24px;
  border-left: 1px solid #cccccc66;
  width: 100%;

}

@media only screen and (max-width: 800px) {
    .hidemenowplease {
        display: none;
    }
    .makemesmaller {
    	width: 50px;
    }
}

</style>

<div class="tabvertcontainer" style="margin-left:-20px; margin-right:-20px; margin-bottom:-45px">
  <div class="tabvert makemesmaller">
    <button class="tablinks" onclick="openMediaToolTab(event, 'convert-tab')" id="defaultOpen" style="margin-top:25px">
        <span class="dashicons dashicons-playlist-video"></span>
        <span class="hidemenowplease">
          <?php _e( 'Video Convert', 'scrollsequence' ); ?>
        </span>
    </button>
    <button class="tablinks" onclick="openMediaToolTab(event, 'frame-former-tab')">
        <span class="dashicons dashicons-images-alt"></span>
    	<span class="hidemenowplease">
      <?php _e( 'Frame Former', 'scrollsequence' ); ?>
      </span>
    </button>
    <button class="tablinks" onclick="openMediaToolTab(event, 'external-tab')"  >
        <span class="dashicons dashicons-hammer"></span>
      <span class="hidemenowplease"><?php _e( 'External Tools', 'scrollsequence' ); ?></span>
    </button>
  </div>

  <div id="convert-tab" class="tabvertcontent">
    <?php require('update_notice.php') ?>
    <?php require_once("scrollsequence-admin-display-media-tools-convert.php");  ?>
  </div>

  <div id="frame-former-tab" class="tabvertcontent">
    <?php require('update_notice.php') ?>
    <?php require_once("scrollsequence-admin-display-media-tools-frameformer.php");  ?>
  </div>

  <div id="external-tab" class="tabvertcontent">
    <?php //require('update_notice.php') ?>
    <?php require_once("scrollsequence-admin-display-media-tools-external.php");  ?>
  </div>
</div>

<script>
function openMediaToolTab(evt, cityName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("tabvertcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(cityName).style.display = "block";
  evt.currentTarget.className += " active";
}

// Get the element with id="defaultOpen" and click on it
document.getElementById("defaultOpen").click();
</script>