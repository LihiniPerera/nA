      <div>
          <p  style="max-width:640px; text-align: center; border:1px solid #c3c4c7; background-color: #f0f0f1; padding-left:1rem; padding-right:1rem;" ><br>
            <strong>
              <?php 
_e( 'Experience the power of our Media Tools, exclusively available to Business Plan users.', 'scrollsequence' );
?>
            <br><br/>
              <?php 
// _e( 'Business Plan Required', 'scrollsequence' );
?>
            </strong><br>
            <a 
              class="button button-primary button-hero" 
              style="background-color:#2271b1; border: 1px solid #2271b1" 
              href="<?php 
echo admin_url( 'admin.php?page=scrollsequence-pricing&checkout=true&plan_name=business&billing_cycle=annual&trial=true' );
?>"
            >
              <span class="dashicons dashicons-hourglass" style=" margin-top: 0.7rem;    margin-right: 0.35rem;"></span>
              <?php 
_e( 'Start Your Business Plan Free Trial Now', 'scrollsequence' );
?>
            </a>
            <br><br>
            <?php 
_e( 'Enjoy a 14-day trial at no cost. A payment method is needed to initiate the trial, you have the freedom to cancel at any point during this period.', 'scrollsequence' );
?>
            <br><br><br>
          </p>
      </div>
<?php 
?>    