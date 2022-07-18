<?php
/**
* Plugin Name: EventOn Divi Addon
* Plugin URI: https://thedamedigital.com/
* Description: EventOn Divi Integration
* Author: The Dame Digital
* Author URI: https://thedamedigital.com/
* License: GPLv2 or later
*/

define('ABSPATH',ABSPATH);

class DAME_DIVIEVENTON_INTEGRATION {

	public function __construct()
	{
        add_action( 'admin_menu', array( &$this, 'dde_menu') );
        add_shortcode( 'dame-eventon-hp',  array( &$this, 'dde_countdown_output' ) );
        wp_enqueue_script( 'dame-app', plugin_dir_url( __FILE__ ) . '/inc/js/dame-app.js', array(), false, true );
	}

	public function dde_menu()
	{
		add_menu_page(
			__( 'Divi Event On Settings', 'dde-settings' ),
			__( 'Divi Event On Settings', 'dde-settings' ),
			'manage_options',
			'dde-settings',
			array(&$this,'dde_settings'),
			'dashicons-schedule',
			3
		);
	}

	public function dde_settings()
	{
        // Settings here
    }

    // Shortcode : Event Countdown
    public function dde_countdown_output( $atts )
    {

        $atts = shortcode_atts(
            array(
                'eventon_id' => 214,
            ), $atts, 'dame-eventon-hp'
        );

        // Filter Data
        $eventOn_ID = esc_html( $atts['eventon_id'] );

        // Get the data from the db and return

        // $event_info data as @array
        // [event_title] => The Event
        // [event_permalink] => http://kidsrock.codemeplz.com/events/the-event/
        // [event_location] => Kids Rock Hotel
        // [event_start_dt] => 20-09-01 08:00
        // [event_end_dt] => 20-09-01 17:00
        // [event_year] => 2020
        // [event_month] => September

        $event_info = $this->get_info_by_eventid($eventOn_ID);

        // Closing Column
        $html  = '[et_pb_row _builder_version="4.5.7" hover_enabled="0" custom_margin="70px||70px||true|false"]';

        // Opening Column
        $html .= '[et_pb_column type="4_4" _builder_version="4.4.8"]';

        // Date / Location
        $html .= '[et_pb_text _builder_version="4.5.7" _module_preset="default" text_text_color="#7a3c8f" text_font_size="16px" hover_enabled="0" custom_margin="||0px||false|false" text_font="|300|||||||" text_letter_spacing="1px" module_class="dee-date-loc"]<p style="text-align: center;">'.$event_info['event_month'].', '.$event_info['event_year'].' / '.$event_info['event_location'].'</p>[/et_pb_text]';

        // Countdown
        $html .= '[et_pb_countdown_timer title="'.$event_info['event_title'].'" date_time="'.$event_info['event_start_dt'].'" _builder_version="4.5.7" _module_preset="default" background_color="rgba(0,0,0,0)" numbers_text_color="#7a3c8f" numbers_font="Kidsrock||||||||" numbers_font_size="73px" header_text_color="#7a3c8f" label_text_color="#7a3c8f" label_font="Kidsrock||||||||" hover_enabled="0" header_font="Fredoka||||||||" header_font_size="38px" header_line_height="2em" custom_padding="0px|||||" module_class="dee-countdown"][/et_pb_countdown_timer]';

        // Button
        $html .= '[et_pb_button button_text="BUY TICKET" _builder_version="4.5.7" _module_preset="default" custom_button="on" button_bg_color="#7a3c8f" button_bg_enable_color="on" button_border_width="0px" button_border_radius="0px" button_use_icon="off" button_text_color="#ffffff" custom_padding="15px|70px|15px|70px|true|true" button_alignment="center" button_text_size="14px" button_letter_spacing="1px" button_font="|600|||||||" hover_enabled="0" button_url="'.$event_info['event_permalink'].'"][/et_pb_button]';

        // Closing Column
        $html .= '[/et_pb_column]';

        // Closing Row
        $html .= '[/et_pb_row]';

        return do_shortcode($html);

    }

    private function get_info_by_eventid($eventon_id)
    {

        global $wpdb;

        // Get information from posts
        $info_post = $wpdb->get_results( " SELECT * FROM {$wpdb->prefix}posts WHERE `post_type`='ajde_events' AND `ID`='$eventon_id' ");

        // Get information from postmeta
        $info_postmetas = $wpdb->get_results( " SELECT * FROM {$wpdb->prefix}postmeta WHERE `post_id`='$eventon_id' ");

        // Convert Timestap
        // 2020-09-01 01:00
        // date("y-m-d H:i");                     // 2020-09-01 01:00
        // date("F j, Y, g:i a");                 // March 10, 2001, 5:16 pm
        // date("m.d.y");                         // 03.10.01
        // date("j, n, Y");                       // 10, 3, 2001
        // date("Ymd");                           // 20010310
        // date('h-i-s, j-m-y, it is w Day');     // 05-16-18, 10-03-01, 1631 1618 6 Satpm01
        // date('\i\t \i\s \t\h\e jS \d\a\y.');   // it is the 10th day.
        // date("D M j G:i:s T Y");               // Sat Mar 10 17:16:18 MST 2001
        // date('H:m:s \m \i\s\ \m\o\n\t\h');     // 17:03:18 m is month
        // date("H:i:s");                         // 17:16:18

        // Get the location of the event
        $info_location = get_the_terms( $eventon_id, array( 'event_location') );
        $info_location = $info_location[0]->name;

        $info_month = get_post_meta($eventon_id, '_event_month', TRUE);
        $info_month_name = date("F", mktime(0, 0, 0, $info_month, 10));

        $info_postmeta_array = array(
            'event_title'       => get_the_title($eventon_id),
            'event_permalink'   => get_the_permalink($eventon_id),
            'event_location'    => $info_location,
            // 'event_start'       => get_post_meta($eventon_id, 'evcal_srow', TRUE),
            'event_start_dt'    => date("Y-m-d H:i",get_post_meta($eventon_id, 'evcal_srow', TRUE)),
            // 'event_end'         => get_post_meta($eventon_id, 'evcal_erow', TRUE),
            'event_end_dt'      => date("Y-m-d H:i",get_post_meta($eventon_id, 'evcal_erow', TRUE)),
            'event_year'        => get_post_meta($eventon_id, 'event_year', TRUE),
            'event_month'       => $info_month_name,
            // 'event_start_date'  => get_post_meta($eventon_id, '_display_start_date', TRUE),
            // 'event_end_date'    => get_post_meta($eventon_id, '_display_end_date', TRUE),
        );

        return($info_postmeta_array);

    }

}
new DAME_DIVIEVENTON_INTEGRATION();

?>