<?php
/**
 * Plugin Name:     Ultimate Member - Birth Date Validation
 * Description:     Extension to Ultimate Member for Birth Date Validation and disables the date picker for birth date field.
 * Version:         1.3.0
 * Requires PHP:    7.4
 * Author:          Miss Veronica
 * License:         GPL v2 or later
 * License URI:     https://www.gnu.org/licenses/gpl-2.0.html
 * Author URI:      https://github.com/MissVeronica
 * Text Domain:     ultimate-member
 * Domain Path:     /languages
 * UM version:      2.7.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
if ( ! class_exists( 'UM' ) ) return;

Class UM_Birth_Date_Validation {

    function __construct() {

        add_action( 'um_submit_form_errors_hook__profile',      array( $this, 'um_submit_form_errors_hook__birthdate' ), 10, 2 );
        add_action( 'um_submit_form_errors_hook__registration', array( $this, 'um_submit_form_errors_hook__birthdate' ), 10, 2 );
        add_filter( 'um_extend_field_classes',                  array( $this, 'um_datepicker_replace_classes' ), 10, 3 );
        add_filter( "um_birth_date_form_edit_field",            array( $this, 'um_birth_date_form_edit_field_error' ), 10, 2 );
    }

    public function um_birth_date_form_edit_field_error( $output, $set_mode ) {

        if ( UM()->fields()->is_error( 'birth_date' ) && str_contains( $output, '1970/01/01' )) {

            $output = str_replace( '1970/01/01', '', $output );
        }
        return $output;
    }

    public function um_datepicker_replace_classes( $classes, $key, $data ) {

        if ( $data['type'] == 'date' || $data['type'] == 'time') {
            $classes = str_replace( array( 'um-datepicker ', 'um-timepicker ' ),
                                    array( 'walcf7-datepicker ', 'walcf7-timepicker ' ), $classes );
        }
        return $classes;
    }

    public function um_submit_form_errors_hook__birthdate( $submitted_data, $form_data ) {

        if ( isset( $submitted_data['birth_date'] )) {

            $birth_date = $submitted_data['birth_date'];

            if ( strlen( $submitted_data['birth_date'] ) == 8 && is_numeric( $submitted_data['birth_date'] )) {
                $submitted_data['birth_date'] = substr( $submitted_data['birth_date'], 0, 4 ) . '/' . substr( $submitted_data['birth_date'], 4, 2 ) . '/' . substr( $submitted_data['birth_date'], 6 );
            }

            if ( empty( $submitted_data['birth_date'] )) {
                UM()->form()->add_error( 'birth_date', __( 'The date of birth is empty. Valid date format is YYYY/MM/DD', 'ultimate-member' ));

            } elseif ( ! preg_match( '~^([0-9]{4})/([0-9]{2})/([0-9]{2})$~', $submitted_data['birth_date'], $parts )) {
                UM()->form()->add_error( 'birth_date', sprintf( __( 'The date of birth is not a valid date in the format YYYY/MM/DD "%s"', 'ultimate-member' ), esc_attr( $birth_date )));

            } elseif ( ! checkdate( $parts[2], $parts[3], $parts[1] )) {
                UM()->form()->add_error( 'birth_date', sprintf( __( 'The date of birth is invalid "%s"', 'ultimate-member' ), esc_attr( $birth_date )));

            } else {

                $custom_fields = maybe_unserialize( $form_data['custom_fields'] );
                $max_years = isset( $custom_fields['birth_date']['years'] ) ? $custom_fields['birth_date']['years'] : '100';

                $dob = new DateTime( $submitted_data['birth_date'] );
                $max_age = new DateInterval( 'P' . $max_years . 'Y' );
                $max_dob_limit = ( new DateTime() )->sub( $max_age );

                if ( $dob <= $max_dob_limit ) {
                    UM()->form()->add_error( 'birth_date', sprintf( __( 'The date of birth is invalid ( older than %s years ).', 'ultimate-member' ), esc_attr( $max_years )));

                } else {

                    if ( $submitted_data['birth_date'] >= date_i18n( 'Y/m/d', current_time( 'timestamp' )) ) {
                        UM()->form()->add_error( 'birth_date', sprintf( __( 'The date of birth is not a past date "%s". Valid date format is YYYY/MM/DD', 'ultimate-member' ), esc_attr( $birth_date )));
                    }
                }
            }
        }
    }

}

new UM_Birth_Date_Validation();
