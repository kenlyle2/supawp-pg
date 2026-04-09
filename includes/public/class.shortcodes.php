<?php

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

/**
 * Shortcodes for SupaWP plugin
 *
 * @category Class
 * @package  SupaWP
 */
class SupaWP_Shortcode {

  /**
   * Initialize the class
   *
   * @return void
   */
  public static function init() {
    add_shortcode('supawp_login', array(__CLASS__, 'login_form'));
    add_shortcode('supawp_signup', array(__CLASS__, 'signup_form'));
    add_shortcode('supawp_logout', array(__CLASS__, 'logout_button'));
    add_shortcode('supawp_auth', array(__CLASS__, 'auth_form'));
    add_shortcode('supawp_launch_app', array(__CLASS__, 'launch_app_button'));
  }

  /**
   * Login form shortcode
   *
   * @param array $atts Shortcode attributes
   * @return string HTML output
   */
  public static function login_form($atts) {
    $atts = shortcode_atts(array(
      'redirect' => '',
      'class' => '',
    ), $atts, 'supawp_login');

    $redirect = !empty($atts['redirect']) ? $atts['redirect'] : '';
    $class = !empty($atts['class']) ? $atts['class'] : '';

    // Get enabled authentication methods
    $options = get_option('supawp_options', array());
    $auth_methods = isset($options['supawp_auth_methods']) && is_array($options['supawp_auth_methods'])
      ? $options['supawp_auth_methods']
      : array('email'); // Default to email only

    $has_password_auth = in_array('email', $auth_methods);
    $has_otp_auth = in_array('email_otp_token', $auth_methods);
    $show_toggle = $has_password_auth && $has_otp_auth;

    ob_start();
?>
    <div class="supawp-login-form <?php echo esc_attr($class); ?>">
      <?php if ($show_toggle) : ?>
        <!-- Toggle between Password and OTP -->
        <div class="supawp-login-toggle" style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd;">
          <button type="button" class="supawp-toggle-btn supawp-toggle-password active" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-weight: 500;">
            <?php _e('Password', 'supawp'); ?>
          </button>
          <button type="button" class="supawp-toggle-btn supawp-toggle-otp" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-weight: 500;">
            <?php _e('Magic Code', 'supawp'); ?>
          </button>
        </div>
      <?php endif; ?>

      <?php if ($has_password_auth) : ?>
        <!-- Password Login Form -->
        <div id="supawp-password-login-container" style="<?php echo $show_toggle ? '' : 'display: block;'; ?>">
          <form id="supawp-login-form" data-redirect="<?php echo esc_url($redirect); ?>">
            <div id="supawp-login-message" class="supawp-message" style="display: none;"></div>

            <div class="supawp-form-group">
              <input type="email" id="supawp-email" name="email" placeholder="<?php _e('Email', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <input type="password" id="supawp-password" name="password" placeholder="<?php _e('Password', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit" id="supawp-login-button"><?php _e('Login', 'supawp'); ?></button>
            </div>
            <div class="supawp-form-group">
              <a href="#" id="supawp-forgot-password-link"><?php _e('Forgot Password?', 'supawp'); ?></a>
            </div>
          </form>

          <!-- Forgot Password Form -->
          <form id="supawp-forgot-password-form" style="display: none;">
            <div id="supawp-forgot-password-message" class="supawp-message" style="display: none;"></div>

            <div class="supawp-form-group">
              <input type="email" id="supawp-forgot-email" name="email" placeholder="<?php _e('Enter your email', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit" id="supawp-forgot-password-button"><?php _e('Reset Password', 'supawp'); ?></button>
            </div>
            <div class="supawp-form-group">
              <a href="#" id="supawp-back-to-login-link"><?php _e('Back to Login', 'supawp'); ?></a>
            </div>
          </form>

          <!-- OTP Password Reset Form (Step 2: Enter OTP Code and New Password) -->
          <form id="supawp-reset-otp-verify-form" style="display: none;">
            <div id="supawp-reset-otp-message" class="supawp-message" style="display: none;"></div>

            <input type="hidden" id="supawp-reset-email-hidden" name="email">

            <div class="supawp-form-group">
              <input type="text" id="supawp-reset-otp-code" name="code" placeholder="<?php _e('Enter 6-digit code', 'supawp'); ?>" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            <div class="supawp-form-group">
              <input type="password" id="supawp-reset-new-password" name="new_password" placeholder="<?php _e('New Password', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <input type="password" id="supawp-reset-confirm-password" name="confirm_password" placeholder="<?php _e('Confirm New Password', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit" id="supawp-reset-password-verify-button"><?php _e('Reset Password', 'supawp'); ?></button>
            </div>
            <div class="supawp-form-group" style="display: flex; gap: 10px; font-size: 14px;">
              <a href="#" id="supawp-reset-back-button"><?php _e('Back', 'supawp'); ?></a>
              <span>|</span>
              <a href="#" id="supawp-reset-resend-button"><?php _e('Resend Code', 'supawp'); ?></a>
            </div>
          </form>
        </div>
      <?php endif; ?>

      <?php if ($has_otp_auth) : ?>
        <!-- OTP Login Container -->
        <div id="supawp-otp-login-container" style="<?php echo $show_toggle ? 'display: none;' : 'display: block;'; ?>">
          <div id="supawp-otp-message" class="supawp-message" style="display: none;"></div>

          <!-- OTP Request Form (Step 1: Enter Email) -->
          <form id="supawp-otp-request-form" data-redirect="<?php echo esc_url($redirect); ?>">
            <div class="supawp-form-group">
              <input type="email" id="supawp-otp-email" name="email" placeholder="<?php _e('Email', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit"><?php _e('Send Code', 'supawp'); ?></button>
            </div>
          </form>

          <!-- OTP Verify Form (Step 2: Enter Code) -->
          <form id="supawp-otp-verify-form" data-redirect="<?php echo esc_url($redirect); ?>" style="display: none;">
            <input type="hidden" id="supawp-otp-email-hidden" name="email">

            <div class="supawp-form-group">
              <input type="text" id="supawp-otp-code" name="code" placeholder="<?php _e('Enter 6-digit code', 'supawp'); ?>" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit"><?php _e('Verify', 'supawp'); ?></button>
            </div>
            <div class="supawp-form-group" style="display: flex; gap: 10px; font-size: 14px;">
              <a href="#" id="supawp-otp-back-button"><?php _e('Back', 'supawp'); ?></a>
              <span>|</span>
              <a href="#" id="supawp-otp-resend-button"><?php _e('Resend Code', 'supawp'); ?></a>
            </div>
          </form>
        </div>
      <?php endif; ?>
    </div>

    <style>
      .supawp-message {
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 4px;
        font-size: 14px;
        line-height: 1.4;
      }

      .supawp-message.error {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
      }

      .supawp-message.success {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
      }

      .supawp-message.info {
        background-color: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
      }

      .supawp-toggle-btn.active {
        border-bottom-color: #4285f4 !important;
        color: #4285f4;
      }

      .supawp-toggle-btn:hover {
        background-color: #f5f5f5;
      }
    </style>

    <?php if ($show_toggle) : ?>
      <script>
        document.addEventListener('DOMContentLoaded', function() {
          const passwordToggle = document.querySelector('.supawp-toggle-password');
          const otpToggle = document.querySelector('.supawp-toggle-otp');
          const passwordContainer = document.getElementById('supawp-password-login-container');
          const otpContainer = document.getElementById('supawp-otp-login-container');

          if (passwordToggle && otpToggle && passwordContainer && otpContainer) {
            passwordToggle.addEventListener('click', function() {
              passwordToggle.classList.add('active');
              otpToggle.classList.remove('active');
              passwordContainer.style.display = 'block';
              otpContainer.style.display = 'none';
            });

            otpToggle.addEventListener('click', function() {
              otpToggle.classList.add('active');
              passwordToggle.classList.remove('active');
              otpContainer.style.display = 'block';
              passwordContainer.style.display = 'none';
            });
          }
        });
      </script>
    <?php endif; ?>
  <?php
    return ob_get_clean();
  }

  /**
   * Signup form shortcode
   *
   * @param array $atts Shortcode attributes
   * @return string HTML output
   */
  public static function signup_form($atts) {
    $atts = shortcode_atts(array(
      'redirect' => '',
      'class' => '',
      'extra_fields' => '',
      'required_fields' => '',
      'email_confirmation' => 'true',
    ), $atts, 'supawp_signup');

    $redirect = !empty($atts['redirect']) ? $atts['redirect'] : '';
    $class = !empty($atts['class']) ? $atts['class'] : '';
    $extra_fields = !empty($atts['extra_fields']) ? $atts['extra_fields'] : '';
    $required_fields = !empty($atts['required_fields']) ? $atts['required_fields'] : '';
    $email_confirmation = $atts['email_confirmation'] === 'false' ? 'false' : 'true';

    ob_start();
  ?>
    <div class="supawp-signup-form <?php echo esc_attr($class); ?>">
      <form id="supawp-signup-form" data-redirect="<?php echo esc_url($redirect); ?>" data-email-confirmation="<?php echo esc_attr($email_confirmation); ?>">
        <div id="supawp-signup-message" class="supawp-message" style="display: none;"></div>

        <?php if (strpos($extra_fields, 'first_name') !== false) : ?>
          <?php $firstNameRequired = strpos($required_fields, 'first_name') !== false ? 'required' : ''; ?>
          <div class="supawp-form-group">
            <input type="text" id="supawp-signup-firstname" name="first_name" placeholder="<?php _e('First Name', 'supawp'); ?>" <?php echo $firstNameRequired; ?>>
          </div>
        <?php endif; ?>

        <?php if (strpos($extra_fields, 'last_name') !== false) : ?>
          <?php $lastNameRequired = strpos($required_fields, 'last_name') !== false ? 'required' : ''; ?>
          <div class="supawp-form-group">
            <input type="text" id="supawp-signup-lastname" name="last_name" placeholder="<?php _e('Last Name', 'supawp'); ?>" <?php echo $lastNameRequired; ?>>
          </div>
        <?php endif; ?>

        <div class="supawp-form-group">
          <input type="email" id="supawp-signup-email" name="email" placeholder="<?php _e('Email', 'supawp'); ?>" required>
        </div>

        <?php if (strpos($extra_fields, 'phone') !== false) : ?>
          <?php $phoneRequired = strpos($required_fields, 'phone') !== false ? 'required' : ''; ?>
          <div class="supawp-form-group">
            <input type="text" id="supawp-signup-phone" name="phone" placeholder="<?php _e('Phone Number', 'supawp'); ?>" <?php echo $phoneRequired; ?>>
          </div>
        <?php endif; ?>

        <?php if (strpos($extra_fields, 'company') !== false) : ?>
          <?php $companyRequired = strpos($required_fields, 'company') !== false ? 'required' : ''; ?>
          <div class="supawp-form-group">
            <input type="text" id="supawp-signup-company" name="company" placeholder="<?php _e('Company', 'supawp'); ?>" <?php echo $companyRequired; ?>>
          </div>
        <?php endif; ?>

        <div class="supawp-form-group">
          <input type="password" id="supawp-signup-password" name="password" placeholder="<?php _e('Password', 'supawp'); ?>" required>
          <div class="password-strength"></div>
        </div>
        <div class="supawp-form-group">
          <input type="password" id="supawp-signup-confirm-password" name="confirm_password" placeholder="<?php _e('Confirm Password', 'supawp'); ?>" required>
        </div>
        <div class="supawp-form-group">
          <button type="submit" id="supawp-signup-button"><?php _e('Sign Up', 'supawp'); ?></button>
        </div>
      </form>
    </div>

    <style>
      .supawp-message {
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 4px;
        font-size: 14px;
        line-height: 1.4;
      }

      .supawp-message.error {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
      }

      .supawp-message.success {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
      }

      .supawp-message.info {
        background-color: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
      }
    </style>
  <?php
    return ob_get_clean();
  }

  /**
   * Combined auth form shortcode (Login & Signup)
   *
   * @param array $atts Shortcode attributes
   * @return string HTML output
   */
  public static function auth_form($atts) {
    $atts = shortcode_atts(array(
      'redirect' => '',
      'class' => '',
      'default' => 'login', // Default view: login or signup
      'extra_fields' => '',
      'required_fields' => '',
      'email_confirmation' => 'true',
    ), $atts, 'supawp_auth');

    $redirect = !empty($atts['redirect']) ? $atts['redirect'] : '';
    $class = !empty($atts['class']) ? $atts['class'] : '';
    $default_view = ($atts['default'] === 'signup') ? 'signup' : 'login';
    $extra_fields = !empty($atts['extra_fields']) ? $atts['extra_fields'] : '';
    $required_fields = !empty($atts['required_fields']) ? $atts['required_fields'] : '';
    $email_confirmation = $atts['email_confirmation'] === 'false' ? 'false' : 'true';

    // Get enabled authentication methods
    $options = get_option('supawp_options', array());
    $auth_methods = isset($options['supawp_auth_methods']) && is_array($options['supawp_auth_methods'])
      ? $options['supawp_auth_methods']
      : array('email'); // Default to email only

    $has_password_auth = in_array('email', $auth_methods);
    $has_otp_auth = in_array('email_otp_token', $auth_methods);
    $show_login_toggle = $has_password_auth && $has_otp_auth;

    ob_start();
  ?>
    <div class="supawp-auth-container">
      <div class="supawp-auth-form <?php echo esc_attr($class); ?>">
        <!-- Social Login/Signup Section -->
        <?php if (in_array('google', $auth_methods)) : ?>
          <div class="supawp-social-login">
            <button id="supawp-google-login" class="supawp-google-button" data-redirect="<?php echo esc_url($redirect); ?>">
              <span class="supawp-google-icon"></span>
              <span class="google-text-login" <?php echo $default_view === 'signup' ? 'style="display: none;"' : ''; ?>><?php _e('Sign in with Google', 'supawp'); ?></span>
              <span class="google-text-signup" <?php echo $default_view === 'login' ? 'style="display: none;"' : ''; ?>><?php _e('Sign up with Google', 'supawp'); ?></span>
            </button>

            <?php if ($has_password_auth || $has_otp_auth) : ?>
              <div class="supawp-divider">
                <span><?php _e('or', 'supawp'); ?></span>
              </div>
            <?php endif; ?>
          </div>
        <?php endif; ?>

        <!-- Toggle between Password and OTP Login (only shown in login view) -->
        <?php if ($show_login_toggle) : ?>
          <div class="supawp-login-toggle supawp-auth-login-only" style="display: <?php echo $default_view === 'login' ? 'flex' : 'none'; ?>; gap: 10px; margin-bottom: 20px; border-bottom: 1px solid #ddd;">
            <button type="button" class="supawp-toggle-btn supawp-toggle-password active" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-weight: 500;">
              <?php _e('Password', 'supawp'); ?>
            </button>
            <button type="button" class="supawp-toggle-btn supawp-toggle-otp" style="flex: 1; padding: 10px; background: none; border: none; border-bottom: 2px solid transparent; cursor: pointer; font-weight: 500;">
              <?php _e('Magic Code', 'supawp'); ?>
            </button>
          </div>
        <?php endif; ?>

        <!-- Email Login/Signup Forms -->
        <?php if ($has_password_auth) : ?>
          <!-- Login Form -->
          <form id="supawp-login-form" class="supawp-auth-form-section" data-redirect="<?php echo esc_url($redirect); ?>" style="<?php echo $default_view === 'login' ? 'display: block;' : 'display: none;'; ?>">
            <div id="supawp-login-message" class="supawp-message" style="display: none;"></div>

            <div class="supawp-form-group">
              <input type="email" id="supawp-email" name="email" placeholder="<?php _e('Email', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <input type="password" id="supawp-password" name="password" placeholder="<?php _e('Password', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit" id="supawp-login-button"><?php _e('Login', 'supawp'); ?></button>
            </div>
            <div class="supawp-form-group">
              <a href="#" id="supawp-auth-forgot-password-link"><?php _e('Forgot Password?', 'supawp'); ?></a>
            </div>
            <div class="supawp-auth-toggle">
              <?php _e('Don\'t have an account?', 'supawp'); ?>
              <a href="#" class="supawp-toggle-signup"><?php _e('Sign Up', 'supawp'); ?></a>
            </div>
          </form>

          <!-- Forgot Password Form -->
          <form id="supawp-auth-forgot-password-form" class="supawp-auth-form-section" style="display: none;">
            <div id="supawp-auth-forgot-password-message" class="supawp-message" style="display: none;"></div>

            <div class="supawp-form-group">
              <input type="email" id="supawp-auth-forgot-email" name="email" placeholder="<?php _e('Enter your email', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit" id="supawp-auth-forgot-password-button"><?php _e('Reset Password', 'supawp'); ?></button>
            </div>
            <div class="supawp-form-group">
              <a href="#" id="supawp-auth-back-to-login-link"><?php _e('Back to Login', 'supawp'); ?></a>
            </div>
          </form>

          <!-- OTP Password Reset Form (Step 2: Enter OTP Code and New Password) -->
          <form id="supawp-auth-reset-otp-verify-form" class="supawp-auth-form-section" style="display: none;">
            <div id="supawp-auth-reset-otp-message" class="supawp-message" style="display: none;"></div>

            <input type="hidden" id="supawp-auth-reset-email-hidden" name="email">

            <div class="supawp-form-group">
              <input type="text" id="supawp-auth-reset-otp-code" name="code" placeholder="<?php _e('Enter 6-digit code', 'supawp'); ?>" maxlength="6" pattern="[0-9]{6}" required>
            </div>
            <div class="supawp-form-group">
              <input type="password" id="supawp-auth-reset-new-password" name="new_password" placeholder="<?php _e('New Password', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <input type="password" id="supawp-auth-reset-confirm-password" name="confirm_password" placeholder="<?php _e('Confirm New Password', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit" id="supawp-auth-reset-password-verify-button"><?php _e('Reset Password', 'supawp'); ?></button>
            </div>
            <div class="supawp-form-group" style="display: flex; gap: 10px; font-size: 14px;">
              <a href="#" id="supawp-auth-reset-back-button"><?php _e('Back', 'supawp'); ?></a>
              <span>|</span>
              <a href="#" id="supawp-auth-reset-resend-button"><?php _e('Resend Code', 'supawp'); ?></a>
            </div>
          </form>

          <!-- Signup Form -->
          <form id="supawp-signup-form" class="supawp-auth-form-section" data-redirect="<?php echo esc_url($redirect); ?>" data-email-confirmation="<?php echo esc_attr($email_confirmation); ?>" style="<?php echo $default_view === 'signup' ? 'display: block;' : 'display: none;'; ?>">
            <div id="supawp-signup-message" class="supawp-message" style="display: none;"></div>

            <?php if (strpos($extra_fields, 'first_name') !== false) : ?>
              <?php $firstNameRequired = strpos($required_fields, 'first_name') !== false ? 'required' : ''; ?>
              <div class="supawp-form-group">
                <input type="text" id="supawp-auth-firstname" name="first_name" placeholder="<?php _e('First Name', 'supawp'); ?>" <?php echo $firstNameRequired; ?>>
              </div>
            <?php endif; ?>

            <?php if (strpos($extra_fields, 'last_name') !== false) : ?>
              <?php $lastNameRequired = strpos($required_fields, 'last_name') !== false ? 'required' : ''; ?>
              <div class="supawp-form-group">
                <input type="text" id="supawp-auth-lastname" name="last_name" placeholder="<?php _e('Last Name', 'supawp'); ?>" <?php echo $lastNameRequired; ?>>
              </div>
            <?php endif; ?>

            <div class="supawp-form-group">
              <input type="email" id="supawp-signup-email" name="email" placeholder="<?php _e('Email', 'supawp'); ?>" required>
            </div>

            <?php if (strpos($extra_fields, 'phone') !== false) : ?>
              <?php $phoneRequired = strpos($required_fields, 'phone') !== false ? 'required' : ''; ?>
              <div class="supawp-form-group">
                <input type="text" id="supawp-auth-phone" name="phone" placeholder="<?php _e('Phone Number', 'supawp'); ?>" <?php echo $phoneRequired; ?>>
              </div>
            <?php endif; ?>

            <?php if (strpos($extra_fields, 'company') !== false) : ?>
              <?php $companyRequired = strpos($required_fields, 'company') !== false ? 'required' : ''; ?>
              <div class="supawp-form-group">
                <input type="text" id="supawp-auth-company" name="company" placeholder="<?php _e('Company', 'supawp'); ?>" <?php echo $companyRequired; ?>>
              </div>
            <?php endif; ?>

            <div class="supawp-form-group">
              <input type="password" id="supawp-signup-password" name="password" placeholder="<?php _e('Password', 'supawp'); ?>" required>
              <div class="password-strength"></div>
            </div>
            <div class="supawp-form-group">
              <input type="password" id="supawp-signup-confirm-password" name="confirm_password" placeholder="<?php _e('Confirm Password', 'supawp'); ?>" required>
            </div>
            <div class="supawp-form-group">
              <button type="submit" id="supawp-signup-button"><?php _e('Sign Up', 'supawp'); ?></button>
            </div>
            <div class="supawp-auth-toggle">
              <?php _e('Already have an account?', 'supawp'); ?>
              <a href="#" class="supawp-toggle-login"><?php _e('Login', 'supawp'); ?></a>
            </div>
          </form>
        <?php endif; ?>

        <!-- OTP Login Forms (only shown in login view) -->
        <?php if ($has_otp_auth) : ?>
          <div id="supawp-auth-otp-login-container" class="supawp-auth-form-section" style="<?php echo ($default_view === 'login' && $show_login_toggle) ? 'display: none;' : ($default_view === 'login' && !$has_password_auth ? 'display: block;' : 'display: none;'); ?>">
            <div id="supawp-auth-otp-message" class="supawp-message" style="display: none;"></div>

            <!-- OTP Request Form (Step 1: Enter Email) -->
            <form id="supawp-auth-otp-request-form" data-redirect="<?php echo esc_url($redirect); ?>">
              <div class="supawp-form-group">
                <input type="email" id="supawp-auth-otp-email" name="email" placeholder="<?php _e('Email', 'supawp'); ?>" required>
              </div>
              <div class="supawp-form-group">
                <button type="submit"><?php _e('Send Code', 'supawp'); ?></button>
              </div>
            </form>

            <!-- OTP Verify Form (Step 2: Enter Code) -->
            <form id="supawp-auth-otp-verify-form" data-redirect="<?php echo esc_url($redirect); ?>" style="display: none;">
              <input type="hidden" id="supawp-auth-otp-email-hidden" name="email">

              <div class="supawp-form-group">
                <input type="text" id="supawp-auth-otp-code" name="code" placeholder="<?php _e('Enter 6-digit code', 'supawp'); ?>" maxlength="6" pattern="[0-9]{6}" required>
              </div>
              <div class="supawp-form-group">
                <button type="submit"><?php _e('Verify', 'supawp'); ?></button>
              </div>
              <div class="supawp-form-group" style="display: flex; gap: 10px; font-size: 14px;">
                <a href="#" id="supawp-auth-otp-back-button"><?php _e('Back', 'supawp'); ?></a>
                <span>|</span>
                <a href="#" id="supawp-auth-otp-resend-button"><?php _e('Resend Code', 'supawp'); ?></a>
              </div>
            </form>

            <div class="supawp-auth-toggle">
              <?php _e('Don\'t have an account?', 'supawp'); ?>
              <a href="#" class="supawp-toggle-signup"><?php _e('Sign Up', 'supawp'); ?></a>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <style>
      .supawp-form-group {
        margin-bottom: 15px;
      }

      .supawp-form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        box-sizing: border-box;
      }

      .supawp-auth-toggle {
        margin-top: 15px;
        text-align: center;
        font-size: 14px;
      }

      .supawp-auth-toggle a {
        color: #4285f4;
        text-decoration: none;
        font-weight: 500;
      }

      .supawp-auth-toggle a:hover {
        text-decoration: underline;
      }

      .supawp-message {
        padding: 12px;
        margin-bottom: 15px;
        border-radius: 4px;
        font-size: 14px;
        line-height: 1.4;
      }

      .supawp-message.error {
        background-color: #fef2f2;
        border: 1px solid #fecaca;
        color: #991b1b;
      }

      .supawp-message.success {
        background-color: #f0fdf4;
        border: 1px solid #bbf7d0;
        color: #166534;
      }

      .supawp-message.info {
        background-color: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
      }

      .supawp-toggle-btn.active {
        border-bottom-color: #4285f4 !important;
        color: #4285f4;
      }

      .supawp-toggle-btn:hover {
        background-color: #f5f5f5;
      }
    </style>

    <script>
      document.addEventListener('DOMContentLoaded', function() {
        // Get elements
        const loginForm = document.getElementById('supawp-login-form');
        const signupForm = document.getElementById('supawp-signup-form');
        const otpLoginContainer = document.getElementById('supawp-auth-otp-login-container');
        const loginToggleContainer = document.querySelector('.supawp-login-toggle');
        const toggleLogin = document.querySelectorAll('.supawp-toggle-login');
        const toggleSignup = document.querySelectorAll('.supawp-toggle-signup');
        const passwordToggle = document.querySelector('.supawp-toggle-password');
        const otpToggle = document.querySelector('.supawp-toggle-otp');
        const googleTextLogin = document.querySelector('.google-text-login');
        const googleTextSignup = document.querySelector('.google-text-signup');
        const loginMessage = document.getElementById('supawp-login-message');
        const signupMessage = document.getElementById('supawp-signup-message');
        const otpMessage = document.getElementById('supawp-auth-otp-message');

        // Function to switch to login view
        function showLoginView() {
          if (loginForm) loginForm.style.display = 'block';
          if (signupForm) signupForm.style.display = 'none';
          if (otpLoginContainer) otpLoginContainer.style.display = 'none';
          if (loginToggleContainer) loginToggleContainer.style.display = 'flex';
          if (googleTextLogin) googleTextLogin.style.display = 'inline';
          if (googleTextSignup) googleTextSignup.style.display = 'none';
          // Clear any previous error messages
          if (loginMessage) loginMessage.style.display = 'none';
          if (signupMessage) signupMessage.style.display = 'none';
          if (otpMessage) otpMessage.style.display = 'none';
        }

        // Function to switch to signup view
        function showSignupView() {
          if (loginForm) loginForm.style.display = 'none';
          if (signupForm) signupForm.style.display = 'block';
          if (otpLoginContainer) otpLoginContainer.style.display = 'none';
          if (loginToggleContainer) loginToggleContainer.style.display = 'none';
          if (googleTextLogin) googleTextLogin.style.display = 'none';
          if (googleTextSignup) googleTextSignup.style.display = 'inline';
          // Clear any previous error messages
          if (loginMessage) loginMessage.style.display = 'none';
          if (signupMessage) signupMessage.style.display = 'none';
          if (otpMessage) otpMessage.style.display = 'none';
        }

        // Add click event listeners for login/signup toggles
        toggleLogin.forEach(function(element) {
          element.addEventListener('click', function(e) {
            e.preventDefault();
            showLoginView();
          });
        });

        toggleSignup.forEach(function(element) {
          element.addEventListener('click', function(e) {
            e.preventDefault();
            showSignupView();
          });
        });

        // Handle password/OTP toggle
        if (passwordToggle && otpToggle && loginForm && otpLoginContainer) {
          passwordToggle.addEventListener('click', function() {
            passwordToggle.classList.add('active');
            otpToggle.classList.remove('active');
            loginForm.style.display = 'block';
            otpLoginContainer.style.display = 'none';
            if (loginMessage) loginMessage.style.display = 'none';
            if (otpMessage) otpMessage.style.display = 'none';
          });

          otpToggle.addEventListener('click', function() {
            otpToggle.classList.add('active');
            passwordToggle.classList.remove('active');
            otpLoginContainer.style.display = 'block';
            loginForm.style.display = 'none';
            if (loginMessage) loginMessage.style.display = 'none';
            if (otpMessage) otpMessage.style.display = 'none';
          });
        }
      });
    </script>
  <?php
    return ob_get_clean();
  }

  /**
   * Logout button shortcode
   *
   * @param array $atts Shortcode attributes
   * @return string HTML output
   */
  public static function logout_button($atts) {
    $atts = shortcode_atts(array(
      'redirect' => '',
      'class' => '',
      'text' => __('Logout', 'supawp'),
    ), $atts, 'supawp_logout');

    $redirect = !empty($atts['redirect']) ? $atts['redirect'] : '';
    $class = !empty($atts['class']) ? $atts['class'] : '';
    $text = !empty($atts['text']) ? $atts['text'] : __('Logout', 'supawp');

    ob_start();
  ?>
    <div class="supawp-logout-container <?php echo esc_attr($class); ?>">
      <button id="supawp-logout-button" data-redirect="<?php echo esc_url($redirect); ?>"><?php echo esc_html($text); ?></button>
    </div>
<?php
    return ob_get_clean();
  }

  /**
   * [supawp_launch_app] shortcode
   *
   * Renders an "Open App" button when the user is logged into WordPress,
   * or a "Log in" link when they are not.  Solves the UX problem where the
   * app button would be meaningless (and broken) for unauthenticated visitors.
   *
   * Attributes:
   *   app_text    — button label when logged in.     Default: "Open App"
   *   login_text  — link label when not logged in.   Default: "Log In"
   *   login_url   — destination when not logged in.  Default: supawp_redirect_after_logout setting, else /account-login/
   *   class       — extra CSS class on the wrapper.  Default: ""
   *
   * Usage:
   *   [supawp_launch_app]
   *   [supawp_launch_app app_text="Go to PostGlider" login_text="Sign In" class="header-btn"]
   */
  public static function launch_app_button($atts) {
    $options = get_option('supawp_options', array());

    $default_login_url = !empty($options['supawp_redirect_after_logout'])
      ? $options['supawp_redirect_after_logout']
      : home_url('/account-login/');

    $atts = shortcode_atts(array(
      'app_text'   => __('Open App', 'supawp'),
      'login_text' => __('Log In', 'supawp'),
      'login_url'  => $default_login_url,
      'class'      => '',
    ), $atts, 'supawp_launch_app');

    $wrapper_class = 'supawp-launch-app' . (!empty($atts['class']) ? ' ' . esc_attr($atts['class']) : '');
    $launch_url    = add_query_arg('supawp_launch_app', '1', home_url('/'));

    ob_start();
    if (is_user_logged_in()) {
      ?>
      <div class="<?php echo esc_attr($wrapper_class); ?>">
        <a href="<?php echo esc_url($launch_url); ?>" class="supawp-launch-app-btn">
          <?php echo esc_html($atts['app_text']); ?>
        </a>
      </div>
      <?php
    } else {
      ?>
      <div class="<?php echo esc_attr($wrapper_class); ?>">
        <a href="<?php echo esc_url($atts['login_url']); ?>" class="supawp-launch-app-login">
          <?php echo esc_html($atts['login_text']); ?>
        </a>
      </div>
      <?php
    }
    return ob_get_clean();
  }
}
