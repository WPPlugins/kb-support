<?php
/**
 * Email Functions
 *
 * Taken from Easy Digital Downloads
 *
 * @package     KBS
 * @subpackage  Emails
 * @copyright   Copyright (c) 2017, Mike Howard
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 * @since       1.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) )
	exit;

/**
 * Email the ticket details to the customer.
 *
 * @since	1.0
 * @param	int		$ticket_id		Ticket ID
 * @param	bool	$admin_notice	Whether to send the admin email notification or not (default: true)
 * @return	void
 */
function kbs_email_ticket_received( $ticket_id, $admin_notice = true ) {

	$disable = kbs_get_option( 'ticket_received_disable_email', false );
	$disable = apply_filters( 'kbs_ticket_received_disable_email', $disable );
	
	if ( ! empty( $disable ) )	{
		return;
	}

	$single       = kbs_get_ticket_label_singular();
	$ticket       = new KBS_Ticket( $ticket_id );
	$ticket_data  = $ticket->get_meta();

	$from_name    = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name    = apply_filters( 'kbs_ticket_from_name', $from_name, $ticket_id, $ticket_data );

	$from_email   = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email   = apply_filters( 'kbs_ticket_from_address', $from_email, $ticket_id, $ticket_data );

	$to_email     = $ticket->email;

	$subject      = kbs_get_option( 'ticket_subject', sprintf( __( 'Support %s Details', 'kb-support' ), $single ) );
	$subject      = apply_filters( 'kbs_ticket_subject', wp_strip_all_tags( $subject ), $ticket_id );
	$subject      = kbs_do_email_tags( $subject, $ticket_id );

	$heading      = kbs_get_option( 'ticket_heading', sprintf( __( 'Support %s Details', 'kb-support' ), $single ) );
	$heading      = apply_filters( 'kbs_ticket_heading', $heading, $ticket_id, $ticket_data );
	$heading      = kbs_do_email_tags( $heading, $ticket_id );

	$attachments  = apply_filters( 'kbs_ticket_attachments', array(), $ticket_id, $ticket_data );
	$message      = kbs_do_email_tags( kbs_get_ticket_logged_email_body_content( $ticket_id, $ticket_data ), $ticket_id );

	$emails       = KBS()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'kbs_ticket_headers', $emails->get_headers(), $ticket_id, $ticket_data );
	$emails->__set( 'headers', $headers );

	$emails->send( $to_email, $subject, $message, $attachments );

	if ( $admin_notice && ! kbs_admin_notices_disabled( $ticket_id ) ) {
		do_action( 'kbs_admin_ticket_notice', $ticket_id, $ticket_data );
	}
} // kbs_email_ticket_received

/**
 * Email to customer when a reply is added to a ticket.
 *
 * @since	1.0
 * @param	int		$ticket_id		Ticket ID
 * @return	void
 */
function kbs_email_ticket_reply( $ticket_id ) {

	if ( ! is_admin() )	{
		return;
	}

	$disable = kbs_get_option( 'ticket_reply_disable_email', false );
	$disable = apply_filters( 'kbs_ticket_reply_disable_email', $disable );
	
	if ( ! empty( $disable ) )	{
		return;
	}

	$single = kbs_get_ticket_label_singular();
	$ticket = new KBS_Ticket( $ticket_id );

	// We do not send reply emails if a ticket is closed.
	if ( 'closed' == $ticket->post_status )	{
		return;
	}

	$ticket_data  = $ticket->get_meta();

	$from_name    = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name    = apply_filters( 'kbs_ticket_reply_from_name', $from_name, $ticket_id, $ticket_data );

	$from_email   = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email   = apply_filters( 'kbs_ticket_reply_from_address', $from_email, $ticket_id, $ticket_data );

	$to_email     = $ticket->email;

	$subject      = kbs_get_option( 'ticket_reply_subject', sprintf( __( 'Your Support %s Received a Reply - ##{ticket_id}##', 'kb-support' ), $single ) );
	$subject      = apply_filters( 'kbs_ticket_reply_subject', wp_strip_all_tags( $subject ), $ticket_id );
	$subject      = kbs_do_email_tags( $subject, $ticket_id );

	$heading      = kbs_get_option( 'ticket_reply_heading', sprintf( __( 'Support %s Update for #{ticket_id}', 'kb-support' ), $single ) );
	$heading      = apply_filters( 'kbs_ticket_reply_heading', $heading, $ticket_id, $ticket_data );
	$heading      = kbs_do_email_tags( $heading, $ticket_id );

	$attachments  = apply_filters( 'kbs_ticket_reply_attachments', array(), $ticket_id, $ticket_data );
	$message      = kbs_do_email_tags( kbs_get_ticket_reply_email_body_content( $ticket_id, $ticket_data ), $ticket_id );

	$emails       = KBS()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'kbs_ticket_reply_headers', $emails->get_headers(), $ticket_id, $ticket_data );
	$emails->__set( 'headers', $headers );

	$emails->send( $to_email, $subject, $message, $attachments );

} // kbs_email_ticket_reply
add_action( 'kbs_reply_to_ticket', 'kbs_email_ticket_reply', 999 );

/**
 * Email to customer when a ticket is closed.
 *
 * @since	1.0
 * @param	int		$ticket_id		Ticket ID
 * @return	void
 */
function kbs_email_ticket_closed( $ticket_id ) {

	$disable = kbs_get_option( 'ticket_closed_disable_email', false );
	$disable = apply_filters( 'kbs_ticket_reply_disable_email', $disable );
	
	if ( ! empty( $disable ) )	{
		return;
	}

	$single = kbs_get_ticket_label_singular();
	$ticket = new KBS_Ticket( $ticket_id );

	// We only send emails if a ticket is closed.
	if ( 'closed' != $ticket->post_status )	{
		return;
	}

	$ticket_data  = $ticket->get_meta();

	$from_name    = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name    = apply_filters( 'kbs_ticket_closed_from_name', $from_name, $ticket_id, $ticket_data );

	$from_email   = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email   = apply_filters( 'kbs_ticket_closed_from_address', $from_email, $ticket_id, $ticket_data );

	$to_email     = $ticket->email;

	$subject      = kbs_get_option( 'ticket_closed_subject', sprintf( __( 'Your Support %s is Closed ##{ticket_id}##', 'kb-support' ), $single ) );
	$subject      = apply_filters( 'kbs_ticket_closed_subject', wp_strip_all_tags( $subject ), $ticket_id );
	$subject      = kbs_do_email_tags( $subject, $ticket_id );

	$heading      = kbs_get_option( 'ticket_closed_heading', sprintf( __( 'Support %s #{ticket_id} Closed', 'kb-support' ), $single ) );
	$heading      = apply_filters( 'kbs_ticket_closed_heading', $heading, $ticket_id, $ticket_data );
	$heading      = kbs_do_email_tags( $heading, $ticket_id );

	$attachments  = apply_filters( 'kbs_ticket_closed_attachments', array(), $ticket_id, $ticket_data );
	$message      = kbs_do_email_tags( kbs_get_ticket_closed_email_body_content( $ticket_id, $ticket_data ), $ticket_id );

	$emails       = KBS()->emails;

	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading', $heading );

	$headers = apply_filters( 'kbs_ticket_closed_headers', $emails->get_headers(), $ticket_id, $ticket_data );
	$emails->__set( 'headers', $headers );

	$emails->send( $to_email, $subject, $message, $attachments );

} // kbs_email_ticket_closed
add_action( 'kbs_close_ticket', 'kbs_email_ticket_closed', 999 );

/**
 * Email the ticket received confirmation to the admin accounts for testing.
 *
 * @since	1.0
 * @return	void
 */
function kbs_email_test_ticket_received() {

	$single = kbs_get_ticket_label_singular();

	$from_name   = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'kbs_ticket_from_name', $from_name, 0, array() );

	$from_email  = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'kbs_test_ticket_from_address', $from_email, 0, array() );

	$subject     = kbs_get_option( 'ticket_subject', sprintf( __( 'Support %s Submitted', 'kb-support' ), $single ) );
	$subject     = apply_filters( 'kbs_ticket_subject', wp_strip_all_tags( $subject ), 0 );
	$subject     = kbs_do_email_tags( $subject, 0 );

	$heading     = kbs_get_option( 'ticket_heading', sprintf( __( 'Support %s Details', 'kb-support' ), $single ) );
	$heading     = apply_filters( 'kbs_ticket_heading', $heading, 0, array() );
	$heading     = kbs_do_email_tags( $heading, 0 );

	$attachments = apply_filters( 'kbs_ticket_attachments', array(), 0, array() );

	$message     = kbs_do_email_tags( kbs_get_ticket_logged_email_body_content( 0, array() ), 0 );

	$emails = KBS()->emails;
	$emails->__set( 'from_name' , $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'heading'   , $heading );

	$headers = apply_filters( 'kbs_receipt_headers', $emails->get_headers(), 0, array() );
	$emails->__set( 'headers', $headers );

	$emails->send( kbs_get_admin_notice_emails(), $subject, $message, $attachments );

} // kbs_email_test_ticket_received

/**
 * Sends the Admin Ticket Notification Email
 *
 * @since	1.0
 * @param	int		$ticket_id		Ticket ID
 * @param	arr		$ticket_data	Ticket Meta and Data
 * @return	void
 */
function kbs_admin_email_ticket_notice( $ticket_id = 0, $ticket_data = array() ) {

	$single    = kbs_get_ticket_label_singular();
	$ticket_id = absint( $ticket_id );

	if ( empty( $ticket_id ) ) {
		return;
	}

	$from_name   = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'kbs_notification_ticket_from_name', $from_name, $ticket_id, $ticket_data );

	$from_email  = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'kbs_notification_ticket_from_address', $from_email, $ticket_id, $ticket_data );

	$subject     = kbs_get_option( 'ticket_notification_subject', sprintf( __( 'New %1Ss logged - Case #%1$s', 'kb-support' ), $single, $ticket_id ) );
	$subject     = apply_filters( 'kbs_admin_ticket_notification_subject', wp_strip_all_tags( $subject ), $ticket_id );
	$subject     = kbs_do_email_tags( $subject, $ticket_id );

	$headers     = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers    .= "Reply-To: ". $from_email . "\r\n";
	//$headers  .= "MIME-Version: 1.0\r\n";
	$headers    .= "Content-Type: text/html; charset=utf-8\r\n";
	$headers     = apply_filters( 'kbs_admin_ticket_notification_headers', $headers, $ticket_id, $ticket_data );

	$attachments = apply_filters( 'kbs_admin_ticket_notification_attachments', array(), $ticket_id, $ticket_data );

	$message     = kbs_get_ticket_notification_email_body_content( $ticket_id, $ticket_data );

	$emails = KBS()->emails;
	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'headers', $headers );
	$emails->__set( 'heading', sprintf( __( 'New %s Received', 'kb-support' ), $single ) );

	$emails->send( kbs_get_admin_notice_emails( $ticket_id ), $subject, $message, $attachments );

} // kbs_admin_email_ticket_notice
add_action( 'kbs_admin_ticket_notice', 'kbs_admin_email_ticket_notice', 10, 2 );

/**
 * Sends the Admin Reply Notification Email
 *
 * @since	1.0
 * @param	int		$reply_id		Reply ID
 * @param	arr		$data			Array of reply data from form
 * @return	void
 */
function kbs_admin_email_reply_notice( $reply_id = 0, $data = array() ) {

	if ( is_admin() || kbs_admin_notices_disabled( $reply_id ) )	{
		return;
	}

	$single    = kbs_get_ticket_label_singular();
	$ticket_id = absint( $reply_id );

	if ( empty( $reply_id ) ) {
		return;
	}

	$ticket_id   = get_post_field( 'post_parent', $reply_id );

	if ( empty( $ticket_id ) ) {
		return;
	}

	$from_name   = kbs_get_option( 'from_name', wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) );
	$from_name   = apply_filters( 'kbs_notification_reply_from_name', $from_name, $ticket_id, $data );

	$from_email  = kbs_get_option( 'from_email', get_bloginfo( 'admin_email' ) );
	$from_email  = apply_filters( 'kbs_notification_reply_from_address', $from_email, $ticket_id, $data );

	$subject     = kbs_get_option( 'reply_notification_subject', sprintf( __( 'New %1Ss Reply Received - %1$s #%1$s', 'kb-support' ), $single, $ticket_id ) );
	$subject     = apply_filters( 'kbs_admin_reply_notification_subject', wp_strip_all_tags( $subject ), $ticket_id );
	$subject     = kbs_do_email_tags( $subject, $ticket_id );

	$headers     = "From: " . stripslashes_deep( html_entity_decode( $from_name, ENT_COMPAT, 'UTF-8' ) ) . " <$from_email>\r\n";
	$headers    .= "Reply-To: ". $from_email . "\r\n";
	//$headers  .= "MIME-Version: 1.0\r\n";
	$headers    .= "Content-Type: text/html; charset=utf-8\r\n";
	$headers     = apply_filters( 'kbs_admin_reply_notification_headers', $headers, $ticket_id, $data );

	$attachments = apply_filters( 'kbs_admin_reply_notification_attachments', array(), $ticket_id, $data );

	$message     = kbs_get_reply_notification_email_body_content( $ticket_id, $data );

	$emails = KBS()->emails;
	$emails->__set( 'from_name', $from_name );
	$emails->__set( 'from_email', $from_email );
	$emails->__set( 'headers', $headers );
	$emails->__set( 'heading', sprintf( __( 'New %s Received', 'kb-support' ), $single ) );

	$emails->send( kbs_get_admin_notice_emails( $ticket_id ), $subject, $message, $attachments );

} // kbs_admin_email_reply_notice
add_action( 'kbs_ticket_customer_reply', 'kbs_admin_email_reply_notice', 10, 2 );

/**
 * Retrieves the emails for which admin notifications are sent to (these can be
 * changed in the KBS Settings)
 *
 * @since	1.0
 * @param	int		$ticket_id	Ticket ID
 * @return	mixed
 */
function kbs_get_admin_notice_emails( $ticket_id = 0 )	{
	$emails = kbs_get_option( 'admin_notice_emails', false );
	$emails = strlen( trim( $emails ) ) > 0 ? $emails : get_bloginfo( 'admin_email' );
	$emails = array_map( 'trim', explode( "\n", $emails ) );

	if ( ! empty( $ticket_id ) )	{
		$agent_id  = kbs_get_agent( $ticket_id );

		if ( ! empty( $agent_id ) )	{
			$agent_data  = get_userdata( $agent_id );

			if ( ! empty( $agent_data ) )	{
				$emails      = str_replace( '{agent}', $agent_data->user_email, $emails );
			}
		}
	}

	return apply_filters( 'kbs_admin_notice_emails', $emails );
} // kbs_get_admin_notice_emails

/**
 * Checks whether admin ticket notices are disabled
 *
 * @since	1.0
 *
 * @param	int		$ticket_id
 * @return	mixed
 */
function kbs_admin_notices_disabled( $ticket_id = 0 ) {
	$ret = kbs_get_option( 'disable_admin_notices', false );
	return (bool) apply_filters( 'kbs_admin_notices_disabled', $ret, $ticket_id );
} // kbs_admin_notices_disabled

/**
 * Get various correctly formatted names used in emails
 *
 * @since	1.0
 * @param	$user_info
 *
 * @return	arr		$email_names
 */
function kbs_get_email_names( $user_info ) {
	$email_names = array();
	$user_info 	= maybe_unserialize( $user_info );

	$email_names['fullname'] = '';
	if ( isset( $user_info['id'] ) && $user_info['id'] > 0 && isset( $user_info['first_name'] ) ) {
		$user_data = get_userdata( $user_info['id'] );
		$email_names['name']      = $user_info['first_name'];
		$email_names['fullname']  = $user_info['first_name'] . ' ' . $user_info['last_name'];
		$email_names['username']  = $user_data->user_login;
	} elseif ( isset( $user_info['first_name'] ) ) {
		$email_names['name']     = $user_info['first_name'];
		$email_names['fullname'] = $user_info['first_name'] . ' ' . $user_info['last_name'];
		$email_names['username'] = $user_info['first_name'];
	} else {
		$email_names['name']     = $user_info['email'];
		$email_names['username'] = $user_info['email'];
	}

	return $email_names;
} // kbs_get_email_names
