<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

if (!defined('ABSPATH')) { exit; }

/** Generated translation payload kept separate from runtime template logic. */
final class EmailTemplateTranslationData
{
    public static function all(): array
    {
    static $cache = null;
    if ($cache !== null) {
        return $cache;
    }

    $cache = [
        'el_GR' => array (
  'booking_created_customer' => 
  array (
'title' => 'Η κράτηση δημιουργήθηκε — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν δημιουργείται μια κράτηση.',
'default_subject' => 'Η κράτησή σας ελήφθη με επιτυχία',
'default_body' => 'Γεια σας {customer_name},

Ευχαριστούμε για την κράτησή σας. Η κράτησή σας ελήφθη με επιτυχία.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Κατάσταση: {status_label}
Πληρωμή: {payment_status_label}

Σύνοψη τιμής:
{price_summary}

Αριθμός κράτησης: #{booking_id}

Ακύρωση κράτησης: {cancellation_url}
Επαναπρογραμματισμός κράτησης: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Νέα κράτηση — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν δημιουργείται νέα κράτηση.',
'default_subject' => 'Ελήφθη νέα κράτηση',
'default_body' => 'Ελήφθη νέα κράτηση.

Πελάτης: {customer_name}
E-mail: {customer_email}
Τηλέφωνο: {customer_phone}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Κατάσταση: {status_label}
Πληρωμή: {payment_status_label}

Σύνοψη τιμής:
{price_summary}

Αριθμός κράτησης: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Η κράτηση επιβεβαιώθηκε — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν επιβεβαιώνεται μια κράτηση.',
'default_subject' => 'Η κράτησή σας επιβεβαιώθηκε',
'default_body' => 'Γεια σας {customer_name},

Η κράτησή σας επιβεβαιώθηκε.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}

Ακύρωση κράτησης: {cancellation_url}
Επαναπρογραμματισμός κράτησης: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Η κράτηση επιβεβαιώθηκε — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν επιβεβαιώνεται μια κράτηση.',
'default_subject' => 'Η κράτηση επιβεβαιώθηκε: #{booking_id}',
'default_body' => 'Μια κράτηση επιβεβαιώθηκε.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Αριθμός κράτησης: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Υπενθύμιση 24 ωρών — πελάτης',
'description' => 'Τοποθετείται αυτόματα στην ουρά 24 ώρες πριν από μια επιβεβαιωμένη κράτηση.',
'default_subject' => 'Υπενθύμιση: η κράτησή σας είναι αύριο',
'default_body' => 'Γεια σας {customer_name},

Αυτή είναι μια υπενθύμιση για την επερχόμενη κράτησή σας.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Ακύρωση κράτησης: {cancellation_url}
Επαναπρογραμματισμός κράτησης: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Υπενθύμιση 2 ωρών — πελάτης',
'description' => 'Τοποθετείται αυτόματα στην ουρά 2 ώρες πριν από μια επιβεβαιωμένη κράτηση.',
'default_subject' => 'Υπενθύμιση: η κράτησή σας ξεκινά σύντομα',
'default_body' => 'Γεια σας {customer_name},

Η κράτησή σας ξεκινά σύντομα.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Η κράτηση ακυρώθηκε — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν ακυρώνεται μια κράτηση.',
'default_subject' => 'Η κράτησή σας ακυρώθηκε',
'default_body' => 'Γεια σας {customer_name},

Η κράτησή σας ακυρώθηκε.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Η κράτηση ακυρώθηκε — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν ακυρώνεται μια κράτηση.',
'default_subject' => 'Η κράτηση ακυρώθηκε: #{booking_id}',
'default_body' => 'Μια κράτηση ακυρώθηκε.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Η κράτηση επαναπρογραμματίστηκε — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν επαναπρογραμματίζεται μια κράτηση.',
'default_subject' => 'Η κράτησή σας επαναπρογραμματίστηκε',
'default_body' => 'Γεια σας {customer_name},

Η κράτησή σας επαναπρογραμματίστηκε.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}

Ακύρωση κράτησης: {cancellation_url}
Επαναπρογραμματισμός κράτησης: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Η κράτηση επαναπρογραμματίστηκε — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν επαναπρογραμματίζεται μια κράτηση.',
'default_subject' => 'Η κράτηση επαναπρογραμματίστηκε: #{booking_id}',
'default_body' => 'Μια κράτηση επαναπρογραμματίστηκε.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Κατάσταση: {status_label}
Πληρωμή: {payment_status_label}
Αριθμός κράτησης: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Η κράτηση ολοκληρώθηκε — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν μια κράτηση επισημαίνεται ως ολοκληρωμένη.',
'default_subject' => 'Σας ευχαριστούμε που μας επιλέξατε.',
'default_body' => 'Γεια σας {customer_name},

Σας ευχαριστούμε που μας επιλέξατε. Η κράτησή σας έχει πλέον ολοκληρωθεί.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Η κράτηση ολοκληρώθηκε — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν μια κράτηση επισημαίνεται ως ολοκληρωμένη.',
'default_subject' => 'Η κράτηση ολοκληρώθηκε: #{booking_id}',
'default_body' => 'Μια κράτηση ολοκληρώθηκε.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Αριθμός κράτησης: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Η υπηρεσία άλλαξε — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν αλλάζει η υπηρεσία ή το πακέτο της κράτησης.',
'default_subject' => 'Η υπηρεσία της κράτησής σας άλλαξε',
'default_body' => 'Γεια σας {customer_name},

Η υπηρεσία της κράτησής σας άλλαξε.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Η υπηρεσία άλλαξε — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν αλλάζει η υπηρεσία ή το πακέτο της κράτησης.',
'default_subject' => 'Η υπηρεσία της κράτησης άλλαξε: #{booking_id}',
'default_body' => 'Η υπηρεσία μιας κράτησης άλλαξε.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Αριθμός κράτησης: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Πληρωμή σε εκκρεμότητα — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν η πληρωμή εκκρεμεί ή απαιτεί ενέργεια.',
'default_subject' => 'Η πληρωμή της κράτησής σας εκκρεμεί',
'default_body' => 'Γεια σας {customer_name},

Η πληρωμή της κράτησής σας εκκρεμεί.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Πληρωμή: {payment_status_label}

Σύνοψη τιμής:
{price_summary}

Αριθμός κράτησης: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Πληρωμή σε εκκρεμότητα — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν η πληρωμή εκκρεμεί ή απαιτεί ενέργεια.',
'default_subject' => 'Εκκρεμεί πληρωμή για την κράτηση #{booking_id}',
'default_body' => 'Η πληρωμή εκκρεμεί.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Πληρωμή: {payment_status_label}

Σύνοψη τιμής:
{price_summary}

Αριθμός κράτησης: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Επιβεβαίωση πληρωμής — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν επιβεβαιώνεται η πληρωμή.',
'default_subject' => 'Η πληρωμή ελήφθη',
'default_body' => 'Γεια σας {customer_name},

Λάβαμε την πληρωμή σας.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Πληρωμή: {payment_status_label}

Σύνοψη τιμής:
{price_summary}

Αριθμός κράτησης: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Επιβεβαίωση πληρωμής — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν επιβεβαιώνεται η πληρωμή.',
'default_subject' => 'Ελήφθη πληρωμή για την κράτηση #{booking_id}',
'default_body' => 'Η πληρωμή ελήφθη.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Πληρωμή: {payment_status_label}

Σύνοψη τιμής:
{price_summary}

Αριθμός κράτησης: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Η πληρωμή απέτυχε — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν αποτυγχάνει η πληρωμή.',
'default_subject' => 'Η πληρωμή απέτυχε',
'default_body' => 'Γεια σας {customer_name},

Η πληρωμή σας δεν μπόρεσε να ολοκληρωθεί.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Η πληρωμή απέτυχε — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν αποτυγχάνει η πληρωμή.',
'default_subject' => 'Η πληρωμή απέτυχε για την κράτηση #{booking_id}',
'default_body' => 'Η πληρωμή απέτυχε.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Πληρωμή: {payment_status_label}
Αριθμός κράτησης: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Η πληρωμή επιστράφηκε — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν επιστρέφεται η πληρωμή.',
'default_subject' => 'Η πληρωμή σας επιστράφηκε',
'default_body' => 'Γεια σας {customer_name},

Η πληρωμή σας επιστράφηκε.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Αριθμός κράτησης: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Η πληρωμή επιστράφηκε — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν επιστρέφεται η πληρωμή.',
'default_subject' => 'Επιστροφή πληρωμής για την κράτηση #{booking_id}',
'default_body' => 'Η πληρωμή επιστράφηκε.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Αριθμός κράτησης: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Δημιουργήθηκε τιμολόγιο — πελάτης',
'description' => 'Τοποθετείται στην ουρά για τον πελάτη όταν δημιουργείται τιμολόγιο.',
'default_subject' => 'Τιμολόγιο για την κράτηση #{booking_id}',
'default_body' => 'Γεια σας {customer_name},

Δημιουργήθηκε τιμολόγιο για την κράτησή σας.

Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}

Σύνοψη τιμής:
{price_summary}

Αριθμός κράτησης: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Δημιουργήθηκε τιμολόγιο — διαχειριστής',
'description' => 'Τοποθετείται στην ουρά για τον διαχειριστή όταν δημιουργείται τιμολόγιο.',
'default_subject' => 'Δημιουργήθηκε τιμολόγιο για την κράτηση #{booking_id}',
'default_body' => 'Δημιουργήθηκε τιμολόγιο.

Πελάτης: {customer_name}
E-mail: {customer_email}
Υπηρεσία: {package_title}
Ημερομηνία: {booking_date}
Ώρα: {start_time} - {end_time}
Αριθμός κράτησης: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Μαγικός σύνδεσμος — πελάτης',
'description' => 'Πρότυπο για μελλοντικά email σύνδεσης πελατών.',
'default_subject' => 'Ο σύνδεσμος σύνδεσής σας',
'default_body' => 'Γεια σας {customer_name},

Χρησιμοποιήστε αυτόν τον σύνδεσμο για να συνδεθείτε στον λογαριασμό σας:

{magic_link}

Αυτός ο σύνδεσμος λήγει σύντομα.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Φόρμα επικοινωνίας — διαχειριστής',
'description' => 'Αποστέλλεται στον διαχειριστή όταν ένας επισκέπτης υποβάλλει τη φόρμα επικοινωνίας του Slotera.',
'default_subject' => '[{site_name}] Νέο μήνυμα επικοινωνίας',
'default_body' => 'Νέο μήνυμα από τη φόρμα επικοινωνίας.

Όνομα: {contact_name}
Email: {contact_email}
Τηλέφωνο: {contact_phone}
Θέμα: {contact_subject}
Μήνυμα:
{contact_message}

Σελίδα: {contact_page_title}
URL: {contact_page_url}
Υποβλήθηκε: {contact_submitted_at}
Γλώσσα: {contact_locale}
IP: {contact_user_ip}
Πράκτορας χρήστη: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Μάρκετινγκ — προώθηση',
'description' => 'Επαναχρησιμοποιήσιμο πρότυπο μάρκετινγκ για προωθητικές καμπάνιες, προσφορές και email επαναφοράς.',
'default_subject' => '{headline}',
'default_body' => 'Γεια σας {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Ειδική προσφορά</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Ο κωδικός προσφοράς σας</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · ισχύει έως {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'ga_IE' => array (
  'booking_created_customer' => 
  array (
'title' => 'Áirithint cruthaithe — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a chruthaítear áirithint.',
'default_subject' => 'Fuarthas d’áirithint go rathúil',
'default_body' => 'Dia duit {customer_name},

Go raibh maith agat as d’áirithint. Fuarthas d’áirithint go rathúil.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Stádas: {status_label}
Íocaíocht: {payment_status_label}

Achoimre praghais:
{price_summary}

Uimhir áirithinte: #{booking_id}

Cuir áirithint ar ceal: {cancellation_url}
Athsceidealú áirithinte: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Áirithint nua — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a chruthaítear áirithint nua.',
'default_subject' => 'Fuarthas áirithint nua',
'default_body' => 'Fuarthas áirithint nua.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Fón: {customer_phone}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Stádas: {status_label}
Íocaíocht: {payment_status_label}

Achoimre praghais:
{price_summary}

Uimhir áirithinte: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Áirithint deimhnithe — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a dheimhnítear áirithint.',
'default_subject' => 'Tá d’áirithint deimhnithe',
'default_body' => 'Dia duit {customer_name},

Tá d’áirithint deimhnithe.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Uimhir áirithinte: #{booking_id}

Cuir áirithint ar ceal: {cancellation_url}
Athsceidealú áirithinte: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Áirithint deimhnithe — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a dheimhnítear áirithint.',
'default_subject' => 'Áirithint deimhnithe: #{booking_id}',
'default_body' => 'Deimhníodh áirithint.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Uimhir áirithinte: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Meabhrúchán 24 uair — custaiméir',
'description' => 'Cuirtear sa scuaine go huathoibríoch é 24 uair roimh áirithint dheimhnithe.',
'default_subject' => 'Meabhrúchán: tá d’áirithint amárach',
'default_body' => 'Dia duit {customer_name},

Seo meabhrúchán maidir le d’áirithint atá le teacht.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Cuir áirithint ar ceal: {cancellation_url}
Athsceidealú áirithinte: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Meabhrúchán 2 uair — custaiméir',
'description' => 'Cuirtear sa scuaine go huathoibríoch é 2 uair roimh áirithint dheimhnithe.',
'default_subject' => 'Meabhrúchán: tosóidh d’áirithint go luath',
'default_body' => 'Dia duit {customer_name},

Tosóidh d’áirithint go luath.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Áirithint curtha ar ceal — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a chuirtear áirithint ar ceal.',
'default_subject' => 'Cuireadh d’áirithint ar ceal',
'default_body' => 'Dia duit {customer_name},

Cuireadh d’áirithint ar ceal.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Uimhir áirithinte: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Áirithint curtha ar ceal — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a chuirtear áirithint ar ceal.',
'default_subject' => 'Áirithint curtha ar ceal: #{booking_id}',
'default_body' => 'Cuireadh áirithint ar ceal.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Uimhir áirithinte: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Áirithint athsceidealaithe — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a athsceidealaítear áirithint.',
'default_subject' => 'Athsceidealaíodh d’áirithint',
'default_body' => 'Dia duit {customer_name},

Athsceidealaíodh d’áirithint.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Uimhir áirithinte: #{booking_id}

Cuir áirithint ar ceal: {cancellation_url}
Athsceidealú áirithinte: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Áirithint athsceidealaithe — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a athsceidealaítear áirithint.',
'default_subject' => 'Áirithint athsceidealaithe: #{booking_id}',
'default_body' => 'Athsceidealaíodh áirithint.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Stádas: {status_label}
Íocaíocht: {payment_status_label}
Uimhir áirithinte: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Áirithint críochnaithe — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a mharcáiltear áirithint críochnaithe.',
'default_subject' => 'Go raibh maith agat as do chuairt',
'default_body' => 'Dia duit {customer_name},

Go raibh maith agat as do chuairt. Tá d’áirithint críochnaithe anois.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Uimhir áirithinte: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Áirithint críochnaithe — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a mharcáiltear áirithint críochnaithe.',
'default_subject' => 'Áirithint críochnaithe: #{booking_id}',
'default_body' => 'Críochnaíodh áirithint.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Uimhir áirithinte: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Pacáiste athraithe — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a athraítear seirbhís nó pacáiste na háirithinte.',
'default_subject' => 'Athraíodh seirbhís d’áirithinte',
'default_body' => 'Dia duit {customer_name},

Athraíodh an tseirbhís do d’áirithint.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Uimhir áirithinte: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Pacáiste athraithe — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a athraítear seirbhís nó pacáiste na háirithinte.',
'default_subject' => 'Seirbhís áirithinte athraithe: #{booking_id}',
'default_body' => 'Athraíodh an tseirbhís d’áirithint.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Uimhir áirithinte: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Íocaíocht ar feitheamh — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair atá íocaíocht ar feitheamh nó gníomh de dhíth.',
'default_subject' => 'Tá íocaíocht d’áirithinte ar feitheamh',
'default_body' => 'Dia duit {customer_name},

Tá íocaíocht d’áirithinte ar feitheamh.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Íocaíocht: {payment_status_label}

Achoimre praghais:
{price_summary}

Uimhir áirithinte: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Íocaíocht ar feitheamh — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair atá íocaíocht ar feitheamh nó gníomh de dhíth.',
'default_subject' => 'Íocaíocht ar feitheamh d’áirithint #{booking_id}',
'default_body' => 'Tá an íocaíocht ar feitheamh.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Íocaíocht: {payment_status_label}

Achoimre praghais:
{price_summary}

Uimhir áirithinte: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Deimhniú íocaíochta — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a dheimhnítear íocaíocht.',
'default_subject' => 'Fuarthas an íocaíocht',
'default_body' => 'Dia duit {customer_name},

Fuaireamar d’íocaíocht.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Íocaíocht: {payment_status_label}

Achoimre praghais:
{price_summary}

Uimhir áirithinte: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Deimhniú íocaíochta — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a dheimhnítear íocaíocht.',
'default_subject' => 'Fuarthas íocaíocht d’áirithint #{booking_id}',
'default_body' => 'Fuarthas an íocaíocht.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Íocaíocht: {payment_status_label}

Achoimre praghais:
{price_summary}

Uimhir áirithinte: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Theip ar an íocaíocht — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a theipeann ar íocaíocht.',
'default_subject' => 'Theip ar an íocaíocht',
'default_body' => 'Dia duit {customer_name},

Níorbh fhéidir d’íocaíocht a chur i gcrích.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Uimhir áirithinte: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Theip ar an íocaíocht — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a theipeann ar íocaíocht.',
'default_subject' => 'Theip ar íocaíocht d’áirithint #{booking_id}',
'default_body' => 'Theip ar an íocaíocht.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Íocaíocht: {payment_status_label}
Uimhir áirithinte: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Íocaíocht aisíoctha — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a aisíoctar íocaíocht.',
'default_subject' => 'Aisíocadh d’íocaíocht',
'default_body' => 'Dia duit {customer_name},

Aisíocadh d’íocaíocht.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Uimhir áirithinte: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Íocaíocht aisíoctha — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a aisíoctar íocaíocht.',
'default_subject' => 'Íocaíocht aisíoctha d’áirithint #{booking_id}',
'default_body' => 'Aisíocadh an íocaíocht.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Uimhir áirithinte: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Sonrasc cruthaithe — custaiméir',
'description' => 'Cuirtear sa scuaine don chustaiméir é nuair a chruthaítear sonrasc.',
'default_subject' => 'Sonrasc d’áirithint #{booking_id}',
'default_body' => 'Dia duit {customer_name},

Cruthaíodh sonrasc do d’áirithint.

Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}

Achoimre praghais:
{price_summary}

Uimhir áirithinte: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Sonrasc cruthaithe — riarthóir',
'description' => 'Cuirtear sa scuaine don riarthóir é nuair a chruthaítear sonrasc.',
'default_subject' => 'Sonrasc cruthaithe d’áirithint #{booking_id}',
'default_body' => 'Cruthaíodh sonrasc.

Custaiméir: {customer_name}
Ríomhphost: {customer_email}
Seirbhís: {package_title}
Dáta: {booking_date}
Am: {start_time} - {end_time}
Uimhir áirithinte: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Nasc draíochta — custaiméir',
'description' => 'Teimpléad do ríomhphoist logála isteach cliant amach anseo.',
'default_subject' => 'Do nasc logála isteach',
'default_body' => 'Dia duit {customer_name},

Úsáid an nasc seo chun logáil isteach i do chuntas:

{magic_link}

Rachaidh an nasc seo in éag go luath.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Foirm teagmhála — riarthóir',
'description' => 'Seoltar chuig an riarthóir é nuair a chuireann cuairteoir foirm teagmhála Slotera isteach.',
'default_subject' => '[{site_name}] Teachtaireacht teagmhála nua',
'default_body' => 'Teachtaireacht nua ón bhfoirm teagmhála.

Ainm: {contact_name}
Ríomhphost: {contact_email}
Fón: {contact_phone}
Ábhar: {contact_subject}
Teachtaireacht:
{contact_message}

Leathanach: {contact_page_title}
URL: {contact_page_url}
Curtha isteach: {contact_submitted_at}
Logchaighdeán: {contact_locale}
IP: {contact_user_ip}
Gníomhaire úsáideora: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Margaíocht — cur chun cinn',
'description' => 'Teimpléad margaíochta in-athúsáidte do fheachtais chur chun cinn, tairiscintí agus ríomhphoist athfhillte.',
'default_subject' => '{headline}',
'default_body' => 'Dia duit {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Tairiscint speisialta</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Do chód tairisceana</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · bailí go dtí {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'lv_LV' => array (
          'booking_created_customer' => 
          array (
            'title' => 'Rezervācija izveidota — klients',
            'description' => 'Ievietots rindā klientam, kad rezervācija tiek izveidota.',
            'default_subject' => 'Saņemta jauna rezervācija',
            'default_body' => 'Labdien, {customer_name}!
        
        Paldies par rezervāciju. Jūsu rezervācija ir veiksmīgi saņemta.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Statuss: {status_label}
        Maksājums: {payment_status_label}
        
        Cenu kopsavilkums:
        {price_summary}
        
        Rezervācijas numurs: #{booking_id}
        
        Atcelt rezervāciju: {cancellation_url}
        Pārcelt rezervāciju: {reschedule_url}',
          ),
          'booking_created_admin' => 
          array (
            'title' => 'Jauna rezervācija — administrators',
            'description' => 'Ievietots rindā administratoram, kad tiek izveidota jauna rezervācija.',
            'default_subject' => 'Saņemta jauna rezervācija',
            'default_body' => 'Saņemta jauna rezervācija.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Tālrunis: {customer_phone}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Statuss: {status_label}
        Maksājums: {payment_status_label}
        
        Cenu kopsavilkums:
        {price_summary}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'booking_confirmed_customer' => 
          array (
            'title' => 'Rezervācija apstiprināta — klients',
            'description' => 'Ievietots rindā klientam, kad rezervācija tiek apstiprināta.',
            'default_subject' => 'Jūsu rezervācija ir apstiprināta',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu rezervācija ir apstiprināta.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Rezervācijas numurs: #{booking_id}
        
        Atcelt rezervāciju: {cancellation_url}
        Pārcelt rezervāciju: {reschedule_url}',
          ),
          'booking_confirmed_admin' => 
          array (
            'title' => 'Rezervācija apstiprināta — administrators',
            'description' => 'Ievietots rindā administratoram, kad rezervācija tiek apstiprināta.',
            'default_subject' => 'Rezervācija apstiprināta: #{booking_id}',
            'default_body' => 'Rezervācija ir apstiprināta.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Rezervācijas numurs: #{booking_id}',
          ),
          'booking_reminder_24h_customer' => 
          array (
            'title' => '24 stundu atgādinājums — klients',
            'description' => 'Automātiski ievietots rindā 24 stundas pirms apstiprinātas rezervācijas.',
            'default_subject' => 'Atgādinājums: jūsu rezervācija ir rīt',
            'default_body' => 'Labdien, {customer_name}!
        
        Šis ir atgādinājums par jūsu gaidāmo rezervāciju.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Atcelt rezervāciju: {cancellation_url}
        Pārcelt rezervāciju: {reschedule_url}',
          ),
          'booking_reminder_2h_customer' => 
          array (
            'title' => '2 stundu atgādinājums — klients',
            'description' => 'Automātiski ievietots rindā 2 stundas pirms apstiprinātas rezervācijas.',
            'default_subject' => 'Atgādinājums: jūsu rezervācija drīz sāksies',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu rezervācija drīz sāksies.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}',
          ),
          'booking_cancelled_customer' => 
          array (
            'title' => 'Rezervācija atcelta — klients',
            'description' => 'Ievietots rindā klientam, kad rezervācija tiek atcelta.',
            'default_subject' => 'Jūsu rezervācija ir atcelta',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu rezervācija ir atcelta.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'booking_cancelled_admin' => 
          array (
            'title' => 'Rezervācija atcelta — administrators',
            'description' => 'Ievietots rindā administratoram, kad rezervācija tiek atcelta.',
            'default_subject' => 'Rezervācija atcelta: #{booking_id}',
            'default_body' => 'Rezervācija ir atcelta.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'booking_rescheduled_customer' => 
          array (
            'title' => 'Rezervācijas laiks mainīts — klients',
            'description' => 'Ievietots rindā klientam, kad tiek mainīts rezervācijas laiks.',
            'default_subject' => 'Jūsu rezervācijas laiks ir mainīts',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu rezervācijas laiks ir mainīts.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Rezervācijas numurs: #{booking_id}
        
        Atcelt rezervāciju: {cancellation_url}
        Pārcelt rezervāciju: {reschedule_url}',
          ),
          'booking_rescheduled_admin' => 
          array (
            'title' => 'Rezervācijas laiks mainīts — administrators',
            'description' => 'Ievietots rindā administratoram, kad tiek mainīts rezervācijas laiks.',
            'default_subject' => 'Rezervācijas laiks mainīts: #{booking_id}',
            'default_body' => 'Rezervācijas laiks ir mainīts.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Statuss: {status_label}
        Maksājums: {payment_status_label}
        Rezervācijas numurs: #{booking_id}',
          ),
          'booking_completed_customer' => 
          array (
            'title' => 'Rezervācija pabeigta — klients',
            'description' => 'Ievietots rindā klientam, kad rezervācija tiek atzīmēta kā pabeigta.',
            'default_subject' => 'Paldies, ka izvēlējāties mūs.',
            'default_body' => 'Labdien, {customer_name}!
        
        Paldies, ka izvēlējāties mūs. Jūsu rezervācija ir pabeigta.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'booking_completed_admin' => 
          array (
            'title' => 'Rezervācija pabeigta — administrators',
            'description' => 'Ievietots rindā administratoram, kad rezervācija tiek atzīmēta kā pabeigta.',
            'default_subject' => 'Rezervācija pabeigta: #{booking_id}',
            'default_body' => 'Rezervācija ir pabeigta.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Rezervācijas numurs: #{booking_id}',
          ),
          'package_changed_customer' => 
          array (
            'title' => 'Pakalpojums mainīts — klients',
            'description' => 'Ievietots rindā klientam, kad tiek mainīts rezervācijas pakalpojums.',
            'default_subject' => 'Jūsu rezervācijas pakalpojums ir mainīts',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu rezervācijas pakalpojums ir mainīts.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'package_changed_admin' => 
          array (
            'title' => 'Pakalpojums mainīts — administrators',
            'description' => 'Ievietots rindā administratoram, kad tiek mainīts rezervācijas pakalpojums.',
            'default_subject' => 'Rezervācijas pakalpojums mainīts: #{booking_id}',
            'default_body' => 'Rezervācijas pakalpojums ir mainīts.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Rezervācijas numurs: #{booking_id}',
          ),
          'payment_pending_customer' => 
          array (
            'title' => 'Maksājums gaida apstrādi — klients',
            'description' => 'Ievietots rindā klientam, kad maksājums gaida apstrādi vai nepieciešama darbība.',
            'default_subject' => 'Jūsu rezervācijas maksājums gaida apstrādi',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu rezervācijas maksājums gaida apstrādi.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Maksājums: {payment_status_label}
        
        Cenu kopsavilkums:
        {price_summary}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'payment_pending_admin' => 
          array (
            'title' => 'Maksājums gaida apstrādi — administrators',
            'description' => 'Ievietots rindā administratoram, kad maksājums gaida apstrādi vai nepieciešama darbība.',
            'default_subject' => 'Maksājums gaida apstrādi rezervācijai #{booking_id}',
            'default_body' => 'Maksājums gaida apstrādi.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Maksājums: {payment_status_label}
        
        Cenu kopsavilkums:
        {price_summary}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'payment_received_customer' => 
          array (
            'title' => 'Maksājuma apstiprinājums — klients',
            'description' => 'Ievietots rindā klientam, kad maksājums tiek apstiprināts.',
            'default_subject' => 'Maksājums saņemts',
            'default_body' => 'Labdien, {customer_name}!
        
        Esam saņēmuši jūsu maksājumu.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Maksājums: {payment_status_label}
        
        Cenu kopsavilkums:
        {price_summary}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'payment_received_admin' => 
          array (
            'title' => 'Maksājuma apstiprinājums — administrators',
            'description' => 'Ievietots rindā administratoram, kad maksājums tiek apstiprināts.',
            'default_subject' => 'Saņemts maksājums par rezervāciju #{booking_id}',
            'default_body' => 'Maksājums saņemts.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Maksājums: {payment_status_label}
        
        Cenu kopsavilkums:
        {price_summary}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'payment_failed_customer' => 
          array (
            'title' => 'Maksājums neizdevās — klients',
            'description' => 'Ievietots rindā klientam, ja maksājums neizdodas.',
            'default_subject' => 'Maksājums neizdevās',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu maksājumu neizdevās pabeigt.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'payment_failed_admin' => 
          array (
            'title' => 'Maksājums neizdevās — administrators',
            'description' => 'Ievietots rindā administratoram, ja maksājums neizdodas.',
            'default_subject' => 'Neizdevās maksājums par rezervāciju #{booking_id}',
            'default_body' => 'Maksājums neizdevās.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Maksājums: {payment_status_label}
        Rezervācijas numurs: #{booking_id}',
          ),
          'payment_refunded_customer' => 
          array (
            'title' => 'Maksājums atmaksāts — klients',
            'description' => 'Ievietots rindā klientam, kad maksājums tiek atmaksāts.',
            'default_subject' => 'Jūsu maksājums ir atmaksāts',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu maksājums ir atmaksāts.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'payment_refunded_admin' => 
          array (
            'title' => 'Maksājums atmaksāts — administrators',
            'description' => 'Ievietots rindā administratoram, kad maksājums tiek atmaksāts.',
            'default_subject' => 'Atmaksāts maksājums par rezervāciju #{booking_id}',
            'default_body' => 'Maksājums atmaksāts.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Rezervācijas numurs: #{booking_id}',
          ),
          'invoice_created_customer' => 
          array (
            'title' => 'Rēķins izveidots — klients',
            'description' => 'Ievietots rindā klientam, kad tiek izveidots rēķins.',
            'default_subject' => 'Rēķins rezervācijai #{booking_id}',
            'default_body' => 'Labdien, {customer_name}!
        
        Jūsu rezervācijai ir izveidots rēķins.
        
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        
        Cenu kopsavilkums:
        {price_summary}
        
        Rezervācijas numurs: #{booking_id}',
          ),
          'invoice_created_admin' => 
          array (
            'title' => 'Rēķins izveidots — administrators',
            'description' => 'Ievietots rindā administratoram, kad tiek izveidots rēķins.',
            'default_subject' => 'Izveidots rēķins rezervācijai #{booking_id}',
            'default_body' => 'Ir izveidots rēķins.
        
        Klients: {customer_name}
        E-pasts: {customer_email}
        Pakalpojums: {package_title}
        Datums: {booking_date}
        Laiks: {start_time} - {end_time}
        Rezervācijas numurs: #{booking_id}',
          ),
          'magic_link_customer' => 
          array (
            'title' => 'Vienreizējā saite — klients',
            'description' => 'Veidne turpmākajiem klientu pieteikšanās e-pastiem.',
            'default_subject' => 'Jūsu pieteikšanās saite',
            'default_body' => 'Labdien, {customer_name}!
        
        Izmantojiet šo saiti, lai pieteiktos savā kontā:
        
        {magic_link}
        
        Šīs saites derīguma termiņš drīz beigsies.',
          ),
          'contact_form_admin' => 
          array (
            'title' => 'Saziņas veidlapa — administrators',
            'description' => 'Nosūtīts administratoram, kad apmeklētājs iesniedz Slotera saziņas veidlapu.',
            'default_subject' => '[{site_name}] Jauns saziņas ziņojums',
            'default_body' => 'Jauns saziņas veidlapas ziņojums.
        
        Vārds: {contact_name}
        Email: {contact_email}
        Tālrunis: {contact_phone}
        Temats: {contact_subject}
        Ziņojums:
        {contact_message}
        
        Lapa: {contact_page_title}
        URL: {contact_page_url}
        Iesniegts: {contact_submitted_at}
        Lokalizācija: {contact_locale}
        IP: {contact_user_ip}
        Lietotāja aģents: {contact_user_agent}',
          ),
          'marketing_promo' => 
          array (
            'title' => 'Mārketings — akcija',
            'description' => 'Atkārtoti izmantojama mārketinga veidne reklāmas kampaņām, piedāvājumiem un atgriešanās e-pastiem.',
            'default_subject' => '{headline}',
            'default_body' => 'Labdien, {customer_name}!
        
        {headline}
        
        {message}
        
        {submessage}
        
        {coupon_code}
        
        {cta_url}',
            'default_html_body' => '<div style="text-align:center;">
          <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Īpašais piedāvājums</p>
          <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
          <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
          <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
          {cta_button}
          <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
            <p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Jūsu piedāvājuma kods</p>
            <p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
            <p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · derīgs līdz {coupon_expires}</p>
          </div>
        </div>',
          ),
        ),
        'no_NO' => array (
  'booking_created_customer' => 
  array (
'title' => 'Bestilling opprettet — kunde',
'description' => 'Legges i kø for kunden når en bestilling opprettes.',
'default_subject' => 'Ny bestilling mottatt',
'default_body' => 'Hei, {customer_name}!

Takk for bestillingen. Bestillingen din er mottatt.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bestillingsstatus: {status_label}
Betaling: {payment_status_label}

Prissammendrag:
{price_summary}

Bestillingsnummer: #{booking_id}

Kanseller bestilling: {cancellation_url}
Flytt bestilling: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Ny bestilling — administrator',
'description' => 'Legges i kø for administratoren når en ny bestilling opprettes.',
'default_subject' => 'Ny bestilling mottatt',
'default_body' => 'Ny bestilling mottatt.

Kunde: {customer_name}
E-post: {customer_email}
Telefon: {customer_phone}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bestillingsstatus: {status_label}
Betaling: {payment_status_label}

Prissammendrag:
{price_summary}

Bestillingsnummer: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Bestilling bekreftet — kunde',
'description' => 'Legges i kø for kunden når en bestilling bekreftes.',
'default_subject' => 'Bestillingen din er bekreftet',
'default_body' => 'Hei, {customer_name}!

Bestillingen din er bekreftet.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}

Kanseller bestilling: {cancellation_url}
Flytt bestilling: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Bestilling bekreftet — administrator',
'description' => 'Legges i kø for administratoren når en bestilling bekreftes.',
'default_subject' => 'Bestilling bekreftet: #{booking_id}',
'default_body' => 'En bestilling er bekreftet.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bestillingsnummer: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => '24-timers påminnelse — kunde',
'description' => 'Legges automatisk i kø 24 timer før en bekreftet bestilling.',
'default_subject' => 'Påminnelse: Bestillingen din er i morgen',
'default_body' => 'Hei, {customer_name}!

Dette er en påminnelse om den kommende bestillingen din.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Kanseller bestilling: {cancellation_url}
Flytt bestilling: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => '2-timers påminnelse — kunde',
'description' => 'Legges automatisk i kø 2 timer før en bekreftet bestilling.',
'default_subject' => 'Påminnelse: Bestillingen din starter snart',
'default_body' => 'Hei, {customer_name}!

Bestillingen din starter snart.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Bestilling kansellert — kunde',
'description' => 'Legges i kø for kunden når en bestilling kanselleres.',
'default_subject' => 'Bestillingen din er kansellert',
'default_body' => 'Hei, {customer_name}!

Bestillingen din er kansellert.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Bestilling kansellert — administrator',
'description' => 'Legges i kø for administratoren når en bestilling kanselleres.',
'default_subject' => 'Bestilling kansellert: #{booking_id}',
'default_body' => 'En bestilling er kansellert.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Bestilling flyttet — kunde',
'description' => 'Legges i kø for kunden når en bestilling flyttes.',
'default_subject' => 'Bestillingen din er flyttet',
'default_body' => 'Hei, {customer_name}!

Bestillingen din er flyttet.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}

Kanseller bestilling: {cancellation_url}
Flytt bestilling: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Bestilling flyttet — administrator',
'description' => 'Legges i kø for administratoren når en bestilling flyttes.',
'default_subject' => 'Bestilling flyttet: #{booking_id}',
'default_body' => 'En bestilling er flyttet.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bestillingsstatus: {status_label}
Betaling: {payment_status_label}
Bestillingsnummer: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Bestilling fullført — kunde',
'description' => 'Legges i kø for kunden når en bestilling merkes som fullført.',
'default_subject' => 'Takk for at du valgte oss.',
'default_body' => 'Hei, {customer_name}!

Takk for at du valgte oss. Bestillingen din er nå fullført.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Bestilling fullført — administrator',
'description' => 'Legges i kø for administratoren når en bestilling merkes som fullført.',
'default_subject' => 'Bestilling fullført: #{booking_id}',
'default_body' => 'En bestilling er fullført.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bestillingsnummer: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Tjeneste endret — kunde',
'description' => 'Legges i kø for kunden når tjenesten i bestillingen endres.',
'default_subject' => 'Tjenesten i bestillingen din er endret',
'default_body' => 'Hei, {customer_name}!

Tjenesten i bestillingen din er endret.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Tjeneste endret — administrator',
'description' => 'Legges i kø for administratoren når tjenesten i bestillingen endres.',
'default_subject' => 'Tjenesten i bestillingen er endret: #{booking_id}',
'default_body' => 'Tjenesten i en bestilling er endret.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bestillingsnummer: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Betaling venter — kunde',
'description' => 'Legges i kø for kunden når en betaling venter eller krever handling.',
'default_subject' => 'Betalingen for bestillingen din venter',
'default_body' => 'Hei, {customer_name}!

Betalingen for bestillingen din venter.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}

Prissammendrag:
{price_summary}

Bestillingsnummer: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Betaling venter — administrator',
'description' => 'Legges i kø for administratoren når en betaling venter eller krever handling.',
'default_subject' => 'Betaling venter for bestilling #{booking_id}',
'default_body' => 'Betalingen venter.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}

Prissammendrag:
{price_summary}

Bestillingsnummer: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Betalingsbekreftelse — kunde',
'description' => 'Legges i kø for kunden når en betaling bekreftes.',
'default_subject' => 'Betaling mottatt',
'default_body' => 'Hei, {customer_name}!

Vi har mottatt betalingen din.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}

Prissammendrag:
{price_summary}

Bestillingsnummer: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Betalingsbekreftelse — administrator',
'description' => 'Legges i kø for administratoren når en betaling bekreftes.',
'default_subject' => 'Betaling mottatt for bestilling #{booking_id}',
'default_body' => 'Betaling mottatt.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}

Prissammendrag:
{price_summary}

Bestillingsnummer: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Betaling mislyktes — kunde',
'description' => 'Legges i kø for kunden når en betaling mislykkes.',
'default_subject' => 'Betalingen mislyktes',
'default_body' => 'Hei, {customer_name}!

Betalingen din kunne ikke fullføres.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Betaling mislyktes — administrator',
'description' => 'Legges i kø for administratoren når en betaling mislykkes.',
'default_subject' => 'Betaling mislyktes for bestilling #{booking_id}',
'default_body' => 'Betalingen mislyktes.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}
Bestillingsnummer: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Betaling refundert — kunde',
'description' => 'Legges i kø for kunden når en betaling refunderes.',
'default_subject' => 'Betalingen din er refundert',
'default_body' => 'Hei, {customer_name}!

Betalingen din er refundert.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bestillingsnummer: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Betaling refundert — administrator',
'description' => 'Legges i kø for administratoren når en betaling refunderes.',
'default_subject' => 'Betaling refundert for bestilling #{booking_id}',
'default_body' => 'Betaling refundert.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bestillingsnummer: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Faktura opprettet — kunde',
'description' => 'Legges i kø for kunden når en faktura opprettes.',
'default_subject' => 'Faktura for bestilling #{booking_id}',
'default_body' => 'Hei, {customer_name}!

Det er opprettet en faktura for bestillingen din.

Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Prissammendrag:
{price_summary}

Bestillingsnummer: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Faktura opprettet — administrator',
'description' => 'Legges i kø for administratoren når en faktura opprettes.',
'default_subject' => 'Faktura opprettet for bestilling #{booking_id}',
'default_body' => 'En faktura er opprettet.

Kunde: {customer_name}
E-post: {customer_email}
Tjeneste: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bestillingsnummer: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Engangslenke — kunde',
'description' => 'Mal for fremtidige e-poster om kundeinnlogging.',
'default_subject' => 'Innloggingslenken din',
'default_body' => 'Hei, {customer_name}!

Bruk denne lenken for å logge inn på kontoen din:

{magic_link}

Denne lenken utløper snart.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Kontaktskjema — administrator',
'description' => 'Sendes til administratoren når en besøkende sender inn kontaktskjemaet fra Slotera.',
'default_subject' => '[{site_name}] Ny kontaktmelding',
'default_body' => 'Ny melding fra kontaktskjema.

Navn: {contact_name}
Email: {contact_email}
Telefon: {contact_phone}
Emne: {contact_subject}
Melding:
{contact_message}

Side: {contact_page_title}
URL: {contact_page_url}
Sendt inn: {contact_submitted_at}
Språkinnstilling: {contact_locale}
IP: {contact_user_ip}
Brukeragent: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Markedsføring — kampanje',
'description' => 'Gjenbrukbar markedsføringsmal for kampanjer, tilbud og e-poster som skal få kunder tilbake.',
'default_subject' => '{headline}',
'default_body' => 'Hei, {customer_name}!

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Spesialtilbud</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Tilbudskoden din</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · gyldig til {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'is_IS' => array (
  'booking_created_customer' => 
  array (
'title' => 'Bókun stofnuð — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar bókun er stofnuð.',
'default_subject' => 'Ný bókun móttekin',
'default_body' => 'Sæl {customer_name},

Takk fyrir bókunina. Bókunin þín hefur borist.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Bókunarstaða: {status_label}
Greiðsla: {payment_status_label}

Verðsamantekt:
{price_summary}

Bókunarnúmer: #{booking_id}

Hætta við bókun: {cancellation_url}
Færa bókun: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Ný bókun — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar ný bókun er stofnuð.',
'default_subject' => 'Ný bókun móttekin',
'default_body' => 'Ný bókun móttekin

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Sími: {customer_phone}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Bókunarstaða: {status_label}
Greiðsla: {payment_status_label}

Verðsamantekt:
{price_summary}

Bókunarnúmer: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Bókun staðfest — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar bókun er staðfest.',
'default_subject' => 'Bókunin þín er staðfest',
'default_body' => 'Sæl {customer_name},

Bókunin þín er staðfest.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Bókunarnúmer: #{booking_id}

Hætta við bókun: {cancellation_url}
Færa bókun: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Bókun staðfest — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar bókun er staðfest.',
'default_subject' => 'Bókun staðfest: #{booking_id}',
'default_body' => 'Bókun hefur verið staðfest.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Bókunarnúmer: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Áminning 24 klst. — viðskiptavinur',
'description' => 'Sett sjálfkrafa í biðröð 24 klukkustundum fyrir staðfesta bókun.',
'default_subject' => 'Áminning: bókunin þín er á morgun',
'default_body' => 'Sæl {customer_name},

Þetta er áminning um væntanlega bókun þína.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Hætta við bókun: {cancellation_url}
Færa bókun: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Áminning 2 klst. — viðskiptavinur',
'description' => 'Sett sjálfkrafa í biðröð 2 klukkustundum fyrir staðfesta bókun.',
'default_subject' => 'Áminning: bókunin þín hefst fljótlega',
'default_body' => 'Sæl {customer_name},

Bókunin þín hefst fljótlega.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Bókun afbókuð — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar bókun er afbókuð.',
'default_subject' => 'Bókunin þín hefur verið afbókuð',
'default_body' => 'Sæl {customer_name},

Bókunin þín hefur verið afbókuð.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Bókunarnúmer: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Bókun afbókuð — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar bókun er afbókuð.',
'default_subject' => 'Bókun afbókuð: #{booking_id}',
'default_body' => 'Bókun hefur verið afbókuð.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Bókunarnúmer: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Bókun færð — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar bókun er færð.',
'default_subject' => 'Bókunin þín hefur verið færð',
'default_body' => 'Sæl {customer_name},

Bókunin þín hefur verið færð.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Bókunarnúmer: #{booking_id}

Hætta við bókun: {cancellation_url}
Færa bókun: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Bókun færð — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar bókun er færð.',
'default_subject' => 'Bókun færð: #{booking_id}',
'default_body' => 'Bókun hefur verið færð.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Bókunarstaða: {status_label}
Greiðsla: {payment_status_label}
Bókunarnúmer: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Bókun lokið — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar bókun er merkt lokið.',
'default_subject' => 'Takk fyrir komuna',
'default_body' => 'Sæl {customer_name},

Takk fyrir komuna. Bókuninni þinni er nú lokið.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Bókunarnúmer: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Bókun lokið — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar bókun er merkt lokið.',
'default_subject' => 'Bókun lokið: #{booking_id}',
'default_body' => 'Bókun er lokið.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Bókunarnúmer: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Þjónustu breytt — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar þjónustu eða pakka bókunar er breytt.',
'default_subject' => 'Þjónustu bókunarinnar hefur verið breytt',
'default_body' => 'Sæl {customer_name},

Þjónustu bókunarinnar þinnar hefur verið breytt.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Bókunarnúmer: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Þjónustu breytt — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar þjónustu eða pakka bókunar er breytt.',
'default_subject' => 'Þjónustu bókunar breytt: #{booking_id}',
'default_body' => 'Þjónustu bókunar hefur verið breytt.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Bókunarnúmer: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Greiðsla í bið — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar greiðsla er í bið eða bíður aðgerðar.',
'default_subject' => 'Greiðsla bókunarinnar er í bið',
'default_body' => 'Sæl {customer_name},

Greiðsla bókunarinnar þinnar er í bið.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Greiðsla: {payment_status_label}

Verðsamantekt:
{price_summary}

Bókunarnúmer: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Greiðsla í bið — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar greiðsla er í bið eða bíður aðgerðar.',
'default_subject' => 'Greiðsla í bið fyrir bókun #{booking_id}',
'default_body' => 'Greiðsla er í bið.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Greiðsla: {payment_status_label}

Verðsamantekt:
{price_summary}

Bókunarnúmer: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Greiðslustaðfesting — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar greiðsla er staðfest.',
'default_subject' => 'Greiðsla móttekin',
'default_body' => 'Sæl {customer_name},

Við höfum móttekið greiðsluna þína.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Greiðsla: {payment_status_label}

Verðsamantekt:
{price_summary}

Bókunarnúmer: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Greiðslustaðfesting — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar greiðsla er staðfest.',
'default_subject' => 'Greiðsla móttekin fyrir bókun #{booking_id}',
'default_body' => 'Greiðsla móttekin.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Greiðsla: {payment_status_label}

Verðsamantekt:
{price_summary}

Bókunarnúmer: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Greiðsla mistókst — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar greiðsla mistekst.',
'default_subject' => 'Greiðsla mistókst',
'default_body' => 'Sæl {customer_name},

Ekki tókst að ljúka greiðslunni þinni.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Bókunarnúmer: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Greiðsla mistókst — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar greiðsla mistekst.',
'default_subject' => 'Greiðsla mistókst fyrir bókun #{booking_id}',
'default_body' => 'Greiðsla mistókst.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Greiðsla: {payment_status_label}
Bókunarnúmer: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Greiðsla endurgreidd — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar greiðsla er endurgreidd.',
'default_subject' => 'Greiðslan þín hefur verið endurgreidd',
'default_body' => 'Sæl {customer_name},

Greiðslan þín hefur verið endurgreidd.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Bókunarnúmer: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Greiðsla endurgreidd — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar greiðsla er endurgreidd.',
'default_subject' => 'Greiðsla endurgreidd fyrir bókun #{booking_id}',
'default_body' => 'Greiðsla endurgreidd.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Bókunarnúmer: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Reikningur stofnaður — viðskiptavinur',
'description' => 'Sett í biðröð fyrir viðskiptavininn þegar reikningur er stofnaður.',
'default_subject' => 'Reikningur fyrir bókun #{booking_id}',
'default_body' => 'Sæl {customer_name},

Reikningur hefur verið stofnaður fyrir bókunina þína.

Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}

Verðsamantekt:
{price_summary}

Bókunarnúmer: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Reikningur stofnaður — stjórnandi',
'description' => 'Sett í biðröð fyrir stjórnanda þegar reikningur er stofnaður.',
'default_subject' => 'Reikningur stofnaður fyrir bókun #{booking_id}',
'default_body' => 'Reikningur hefur verið stofnaður.

Viðskiptavinur: {customer_name}
Tölvupóstur: {customer_email}
Þjónusta: {package_title}
Dagsetning: {booking_date}
Tími: {start_time} - {end_time}
Bókunarnúmer: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Innskráningartengill — viðskiptavinur',
'description' => 'Sniðmát fyrir framtíðar tölvupósta um innskráningu viðskiptavina.',
'default_subject' => 'Innskráningartengillinn þinn',
'default_body' => 'Sæl {customer_name},

Notaðu þennan tengil til að skrá þig inn á reikninginn þinn:

{magic_link}

Tengillinn rennur fljótlega út.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Samskiptaeyðublað — stjórnandi',
'description' => 'Sent stjórnanda þegar gestur sendir inn samskiptaeyðublað Slotera.',
'default_subject' => '[{site_name}] Ný skilaboð úr samskiptaeyðublaði',
'default_body' => 'Ný skilaboð úr samskiptaeyðublaði.

Nafn: {contact_name}
Tölvupóstur: {contact_email}
Sími: {contact_phone}
Efni: {contact_subject}
Skilaboð:
{contact_message}

Síða: {contact_page_title}
Vefslóð: {contact_page_url}
Sent: {contact_submitted_at}
Tungumál: {contact_locale}
IP-tala: {contact_user_ip}
Vafraauðkenni: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Markaðssetning — kynning',
'description' => 'Endurnýtanlegt markaðssniðmát fyrir kynningarherferðir, tilboð og endurkomupósta.',
'default_subject' => '{headline}',
'default_body' => 'Sæl {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Sérstakt tilboð</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Tilboðskóðinn þinn</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · gildir til {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'hu_HU' => array (
  'booking_created_customer' => array (
'title' => 'Foglalás létrehozva — ügyfél',
'description' => 'A foglalás létrehozásakor az ügyfél számára kerül sorba.',
'default_subject' => 'Új foglalás érkezett',
'default_body' => 'Kedves {customer_name}!

Köszönjük foglalását. Foglalása sikeresen beérkezett.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Foglalás állapota: {status_label}
Fizetés: {payment_status_label}

Árösszesítő:
{price_summary}

Foglalási szám: #{booking_id}

Foglalás lemondása: {cancellation_url}
Foglalás átütemezése: {reschedule_url}',
  ),
  'booking_created_admin' => array (
'title' => 'Új foglalás — adminisztrátor',
'description' => 'Új foglalás létrehozásakor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Új foglalás érkezett',
'default_body' => 'Új foglalás érkezett.

Ügyfél: {customer_name}
E-mail: {customer_email}
Telefon: {customer_phone}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Foglalás állapota: {status_label}
Fizetés: {payment_status_label}

Árösszesítő:
{price_summary}

Foglalási szám: #{booking_id}',
  ),
  'booking_confirmed_customer' => array (
'title' => 'Foglalás megerősítve — ügyfél',
'description' => 'A foglalás megerősítésekor az ügyfél számára kerül sorba.',
'default_subject' => 'Foglalását megerősítettük',
'default_body' => 'Kedves {customer_name}!

Foglalását megerősítettük.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}

Foglalás lemondása: {cancellation_url}
Foglalás átütemezése: {reschedule_url}',
  ),
  'booking_confirmed_admin' => array (
'title' => 'Foglalás megerősítve — adminisztrátor',
'description' => 'A foglalás megerősítésekor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Foglalás megerősítve: #{booking_id}',
'default_body' => 'Egy foglalást megerősítettek.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Foglalási szám: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => array (
'title' => '24 órás emlékeztető — ügyfél',
'description' => 'Automatikusan sorba kerül 24 órával a megerősített foglalás előtt.',
'default_subject' => 'Emlékeztető: foglalása holnap lesz',
'default_body' => 'Kedves {customer_name}!

Emlékeztetjük közelgő foglalására.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalás lemondása: {cancellation_url}
Foglalás átütemezése: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => array (
'title' => '2 órás emlékeztető — ügyfél',
'description' => 'Automatikusan sorba kerül 2 órával a megerősített foglalás előtt.',
'default_subject' => 'Emlékeztető: foglalása hamarosan kezdődik',
'default_body' => 'Kedves {customer_name}!

Foglalása hamarosan kezdődik.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => array (
'title' => 'Foglalás lemondva — ügyfél',
'description' => 'A foglalás lemondásakor az ügyfél számára kerül sorba.',
'default_subject' => 'Foglalását lemondták',
'default_body' => 'Kedves {customer_name}!

Foglalását lemondták.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}',
  ),
  'booking_cancelled_admin' => array (
'title' => 'Foglalás lemondva — adminisztrátor',
'description' => 'A foglalás lemondásakor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Foglalás lemondva: #{booking_id}',
'default_body' => 'Egy foglalást lemondtak.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}',
  ),
  'booking_rescheduled_customer' => array (
'title' => 'Foglalás átütemezve — ügyfél',
'description' => 'A foglalás átütemezésekor az ügyfél számára kerül sorba.',
'default_subject' => 'Foglalását átütemezték',
'default_body' => 'Kedves {customer_name}!

Foglalását átütemezték.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}

Foglalás lemondása: {cancellation_url}
Foglalás átütemezése: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => array (
'title' => 'Foglalás átütemezve — adminisztrátor',
'description' => 'A foglalás átütemezésekor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Foglalás átütemezve: #{booking_id}',
'default_body' => 'Egy foglalást átütemeztek.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Foglalás állapota: {status_label}
Fizetés: {payment_status_label}
Foglalási szám: #{booking_id}',
  ),
  'booking_completed_customer' => array (
'title' => 'Foglalás teljesítve — ügyfél',
'description' => 'Amikor a foglalást teljesítettként jelölik, az ügyfél számára kerül sorba.',
'default_subject' => 'Köszönjük, hogy minket választott.',
'default_body' => 'Kedves {customer_name}!

Köszönjük, hogy minket választott. Foglalása teljesült.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}',
  ),
  'booking_completed_admin' => array (
'title' => 'Foglalás teljesítve — adminisztrátor',
'description' => 'Amikor a foglalást teljesítettként jelölik, az adminisztrátor számára kerül sorba.',
'default_subject' => 'Foglalás teljesítve: #{booking_id}',
'default_body' => 'Egy foglalás teljesült.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Foglalási szám: #{booking_id}',
  ),
  'package_changed_customer' => array (
'title' => 'Szolgáltatás módosítva — ügyfél',
'description' => 'A foglalás szolgáltatásának vagy csomagjának módosításakor az ügyfél számára kerül sorba.',
'default_subject' => 'Foglalásának szolgáltatása módosult',
'default_body' => 'Kedves {customer_name}!

Foglalásának szolgáltatása módosult.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}',
  ),
  'package_changed_admin' => array (
'title' => 'Szolgáltatás módosítva — adminisztrátor',
'description' => 'A foglalás szolgáltatásának vagy csomagjának módosításakor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Foglalási szolgáltatás módosítva: #{booking_id}',
'default_body' => 'Egy foglalás szolgáltatása módosult.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Foglalási szám: #{booking_id}',
  ),
  'payment_pending_customer' => array (
'title' => 'Fizetés függőben — ügyfél',
'description' => 'Amikor a fizetés függőben van vagy beavatkozásra vár, az ügyfél számára kerül sorba.',
'default_subject' => 'Foglalásának fizetése függőben van',
'default_body' => 'Kedves {customer_name}!

Foglalásának fizetése függőben van.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Fizetés: {payment_status_label}

Árösszesítő:
{price_summary}

Foglalási szám: #{booking_id}',
  ),
  'payment_pending_admin' => array (
'title' => 'Fizetés függőben — adminisztrátor',
'description' => 'Amikor a fizetés függőben van vagy beavatkozásra vár, az adminisztrátor számára kerül sorba.',
'default_subject' => 'Függőben lévő fizetés a(z) #{booking_id} foglaláshoz',
'default_body' => 'A fizetés függőben van.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Fizetés: {payment_status_label}

Árösszesítő:
{price_summary}

Foglalási szám: #{booking_id}',
  ),
  'payment_received_customer' => array (
'title' => 'Fizetési visszaigazolás — ügyfél',
'description' => 'A fizetés megerősítésekor az ügyfél számára kerül sorba.',
'default_subject' => 'Fizetés beérkezett',
'default_body' => 'Kedves {customer_name}!

Fizetését megkaptuk.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Fizetés: {payment_status_label}

Árösszesítő:
{price_summary}

Foglalási szám: #{booking_id}',
  ),
  'payment_received_admin' => array (
'title' => 'Fizetési visszaigazolás — adminisztrátor',
'description' => 'A fizetés megerősítésekor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Fizetés érkezett a(z) #{booking_id} foglaláshoz',
'default_body' => 'Fizetés érkezett.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Fizetés: {payment_status_label}

Árösszesítő:
{price_summary}

Foglalási szám: #{booking_id}',
  ),
  'payment_failed_customer' => array (
'title' => 'Sikertelen fizetés — ügyfél',
'description' => 'Sikertelen fizetéskor az ügyfél számára kerül sorba.',
'default_subject' => 'A fizetés sikertelen',
'default_body' => 'Kedves {customer_name}!

A fizetést nem sikerült befejezni.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}',
  ),
  'payment_failed_admin' => array (
'title' => 'Sikertelen fizetés — adminisztrátor',
'description' => 'Sikertelen fizetéskor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Sikertelen fizetés a(z) #{booking_id} foglaláshoz',
'default_body' => 'A fizetés sikertelen.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Fizetés: {payment_status_label}
Foglalási szám: #{booking_id}',
  ),
  'payment_refunded_customer' => array (
'title' => 'Fizetés visszatérítve — ügyfél',
'description' => 'A fizetés visszatérítésekor az ügyfél számára kerül sorba.',
'default_subject' => 'Fizetését visszatérítettük',
'default_body' => 'Kedves {customer_name}!

Fizetését visszatérítettük.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Foglalási szám: #{booking_id}',
  ),
  'payment_refunded_admin' => array (
'title' => 'Fizetés visszatérítve — adminisztrátor',
'description' => 'A fizetés visszatérítésekor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Fizetés visszatérítve a(z) #{booking_id} foglaláshoz',
'default_body' => 'A fizetést visszatérítették.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Foglalási szám: #{booking_id}',
  ),
  'invoice_created_customer' => array (
'title' => 'Számla létrehozva — ügyfél',
'description' => 'A számla létrehozásakor az ügyfél számára kerül sorba.',
'default_subject' => 'Számla a(z) #{booking_id} foglaláshoz',
'default_body' => 'Kedves {customer_name}!

Foglalásához számla készült.

Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}

Árösszesítő:
{price_summary}

Foglalási szám: #{booking_id}',
  ),
  'invoice_created_admin' => array (
'title' => 'Számla létrehozva — adminisztrátor',
'description' => 'A számla létrehozásakor az adminisztrátor számára kerül sorba.',
'default_subject' => 'Számla készült a(z) #{booking_id} foglaláshoz',
'default_body' => 'Számla készült.

Ügyfél: {customer_name}
E-mail: {customer_email}
Szolgáltatás: {package_title}
Dátum: {booking_date}
Időpont: {start_time} - {end_time}
Foglalási szám: #{booking_id}',
  ),
  'magic_link_customer' => array (
'title' => 'Belépési hivatkozás — ügyfél',
'description' => 'Sablon a jövőbeli ügyfél-bejelentkezési e-mailekhez.',
'default_subject' => 'Az Ön belépési hivatkozása',
'default_body' => 'Kedves {customer_name}!

Az alábbi hivatkozással jelentkezhet be fiókjába:

{magic_link}

A hivatkozás hamarosan lejár.',
  ),
  'contact_form_admin' => array (
'title' => 'Kapcsolatfelvételi űrlap — adminisztrátor',
'description' => 'A Slotera kapcsolatfelvételi űrlapjának elküldésekor az adminisztrátornak küldve.',
'default_subject' => '[{site_name}] Új kapcsolatfelvételi üzenet',
'default_body' => 'Új üzenet érkezett a kapcsolatfelvételi űrlapról.

Név: {contact_name}
E-mail: {contact_email}
Telefon: {contact_phone}
Tárgy: {contact_subject}
Üzenet:
{contact_message}

Oldal: {contact_page_title}
URL: {contact_page_url}
Elküldve: {contact_submitted_at}
Nyelvi beállítás: {contact_locale}
IP-cím: {contact_user_ip}
Felhasználói ügynök: {contact_user_agent}',
  ),
  'marketing_promo' => array (
'title' => 'Marketing — promóció',
'description' => 'Újra felhasználható marketingsablon promóciós kampányokhoz, ajánlatokhoz és visszacsábító e-mailekhez.',
'default_subject' => '{headline}',
'default_body' => 'Kedves {customer_name}!

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Különleges ajánlat</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Az Ön ajánlati kódja</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · érvényes eddig: {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'sl_SI' => array (
  'booking_created_customer' => array (
'title' => 'Ustvarjena rezervacija — stranka',
'description' => 'V čakalno vrsto za stranko ob ustvarjeni rezervaciji.',
'default_subject' => 'Prejeli smo vašo zahtevo za rezervacijo',
'default_body' => 'Pozdravljeni, {customer_name},

Hvala za rezervacijo. Vašo zahtevo smo prejeli.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Stanje rezervacije: {status_label}
Plačilo: {payment_status_label}

Povzetek cene:
{price_summary}

Številka rezervacije: #{booking_id}

Prekliči rezervacijo: {cancellation_url}
Spremeni termin rezervacije: {reschedule_url}',
  ),
  'booking_created_admin' => array (
'title' => 'Nova rezervacija — skrbnik',
'description' => 'V čakalno vrsto za skrbnika ob ustvarjeni novi rezervaciji.',
'default_subject' => 'Prejeta nova rezervacija',
'default_body' => 'Prejeta nova rezervacija.

Stranka: {customer_name}
E-pošta: {customer_email}
Telefon: {customer_phone}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Stanje rezervacije: {status_label}
Plačilo: {payment_status_label}

Povzetek cene:
{price_summary}

Številka rezervacije: #{booking_id}',
  ),
  'booking_confirmed_customer' => array (
'title' => 'Potrjena rezervacija — stranka',
'description' => 'V čakalno vrsto za stranko ob potrditvi rezervacije.',
'default_subject' => 'Vaša rezervacija je potrjena',
'default_body' => 'Pozdravljeni, {customer_name},

Vaša rezervacija je potrjena.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}

Prekliči rezervacijo: {cancellation_url}
Spremeni termin rezervacije: {reschedule_url}',
  ),
  'booking_confirmed_admin' => array (
'title' => 'Potrjena rezervacija — skrbnik',
'description' => 'V čakalno vrsto za skrbnika ob potrditvi rezervacije.',
'default_subject' => 'Rezervacija potrjena: #{booking_id}',
'default_body' => 'Rezervacija je bila potrjena.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Številka rezervacije: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => array (
'title' => 'Opomnik 24 ur — stranka',
'description' => 'Samodejno v čakalno vrsto 24 ur pred potrjeno rezervacijo.',
'default_subject' => 'Opomnik: vaša rezervacija je jutri',
'default_body' => 'Pozdravljeni, {customer_name},

To je opomnik za vašo prihajajočo rezervacijo.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Prekliči rezervacijo: {cancellation_url}
Spremeni termin rezervacije: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => array (
'title' => 'Opomnik 2 uri — stranka',
'description' => 'Samodejno v čakalno vrsto 2 uri pred potrjeno rezervacijo.',
'default_subject' => 'Opomnik: vaša rezervacija se začne kmalu',
'default_body' => 'Pozdravljeni, {customer_name},

Vaša rezervacija se začne kmalu.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => array (
'title' => 'Preklicana rezervacija — stranka',
'description' => 'V čakalno vrsto za stranko ob preklicu rezervacije.',
'default_subject' => 'Vaša rezervacija je bila preklicana',
'default_body' => 'Pozdravljeni, {customer_name},

Vaša rezervacija je bila preklicana.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}',
  ),
  'booking_cancelled_admin' => array (
'title' => 'Preklicana rezervacija — skrbnik',
'description' => 'V čakalno vrsto za skrbnika ob preklicu rezervacije.',
'default_subject' => 'Rezervacija preklicana: #{booking_id}',
'default_body' => 'Rezervacija je bila preklicana.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}',
  ),
  'booking_rescheduled_customer' => array (
'title' => 'Prestavljena rezervacija — stranka',
'description' => 'V čakalno vrsto za stranko ob spremembi termina rezervacije.',
'default_subject' => 'Termin vaše rezervacije je bil spremenjen',
'default_body' => 'Pozdravljeni, {customer_name},

Termin vaše rezervacije je bil spremenjen.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}

Prekliči rezervacijo: {cancellation_url}
Spremeni termin rezervacije: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => array (
'title' => 'Prestavljena rezervacija — skrbnik',
'description' => 'V čakalno vrsto za skrbnika ob spremembi termina rezervacije.',
'default_subject' => 'Termin rezervacije spremenjen: #{booking_id}',
'default_body' => 'Termin rezervacije je bil spremenjen.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Stanje rezervacije: {status_label}
Plačilo: {payment_status_label}
Številka rezervacije: #{booking_id}',
  ),
  'booking_completed_customer' => array (
'title' => 'Zaključena rezervacija — stranka',
'description' => 'V čakalno vrsto za stranko, ko je rezervacija označena kot zaključena.',
'default_subject' => 'Hvala, ker ste izbrali nas.',
'default_body' => 'Pozdravljeni, {customer_name},

Hvala, ker ste izbrali nas. Vaša rezervacija je zdaj zaključena.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}',
  ),
  'booking_completed_admin' => array (
'title' => 'Zaključena rezervacija — skrbnik',
'description' => 'V čakalno vrsto za skrbnika, ko je rezervacija označena kot zaključena.',
'default_subject' => 'Rezervacija zaključena: #{booking_id}',
'default_body' => 'Rezervacija je bila zaključena.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Številka rezervacije: #{booking_id}',
  ),
  'package_changed_customer' => array (
'title' => 'Spremenjena storitev — stranka',
'description' => 'V čakalno vrsto za stranko ob spremembi storitve ali paketa rezervacije.',
'default_subject' => 'Storitev vaše rezervacije je bila spremenjena',
'default_body' => 'Pozdravljeni, {customer_name},

Storitev za vašo rezervacijo je bila spremenjena.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}',
  ),
  'package_changed_admin' => array (
'title' => 'Spremenjena storitev — skrbnik',
'description' => 'V čakalno vrsto za skrbnika ob spremembi storitve ali paketa rezervacije.',
'default_subject' => 'Storitev rezervacije spremenjena: #{booking_id}',
'default_body' => 'Storitev rezervacije je bila spremenjena.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Številka rezervacije: #{booking_id}',
  ),
  'payment_pending_customer' => array (
'title' => 'Plačilo v teku — stranka',
'description' => 'V čakalno vrsto za stranko, ko plačilo čaka ali zahteva dejanje.',
'default_subject' => 'Plačilo za vašo rezervacijo je v teku',
'default_body' => 'Pozdravljeni, {customer_name},

Plačilo za vašo rezervacijo je v teku.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Plačilo: {payment_status_label}

Povzetek cene:
{price_summary}

Številka rezervacije: #{booking_id}',
  ),
  'payment_pending_admin' => array (
'title' => 'Plačilo v teku — skrbnik',
'description' => 'V čakalno vrsto za skrbnika, ko plačilo čaka ali zahteva dejanje.',
'default_subject' => 'Plačilo za rezervacijo #{booking_id} je v teku',
'default_body' => 'Plačilo je v teku.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Plačilo: {payment_status_label}

Povzetek cene:
{price_summary}

Številka rezervacije: #{booking_id}',
  ),
  'payment_received_customer' => array (
'title' => 'Potrditev plačila — stranka',
'description' => 'V čakalno vrsto za stranko ob potrditvi plačila.',
'default_subject' => 'Plačilo prejeto',
'default_body' => 'Pozdravljeni, {customer_name},

Prejeli smo vaše plačilo.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Plačilo: {payment_status_label}

Povzetek cene:
{price_summary}

Številka rezervacije: #{booking_id}',
  ),
  'payment_received_admin' => array (
'title' => 'Potrditev plačila — skrbnik',
'description' => 'V čakalno vrsto za skrbnika ob potrditvi plačila.',
'default_subject' => 'Plačilo prejeto za rezervacijo #{booking_id}',
'default_body' => 'Plačilo je bilo prejeto.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Plačilo: {payment_status_label}

Povzetek cene:
{price_summary}

Številka rezervacije: #{booking_id}',
  ),
  'payment_failed_customer' => array (
'title' => 'Plačilo ni uspelo — stranka',
'description' => 'V čakalno vrsto za stranko, ko plačilo ne uspe.',
'default_subject' => 'Plačilo ni uspelo',
'default_body' => 'Pozdravljeni, {customer_name},

Vašega plačila ni bilo mogoče dokončati.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}',
  ),
  'payment_failed_admin' => array (
'title' => 'Plačilo ni uspelo — skrbnik',
'description' => 'V čakalno vrsto za skrbnika, ko plačilo ne uspe.',
'default_subject' => 'Plačilo ni uspelo za rezervacijo #{booking_id}',
'default_body' => 'Plačilo ni uspelo.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Plačilo: {payment_status_label}
Številka rezervacije: #{booking_id}',
  ),
  'payment_refunded_customer' => array (
'title' => 'Plačilo povrnjeno — stranka',
'description' => 'V čakalno vrsto za stranko ob vračilu plačila.',
'default_subject' => 'Vaše plačilo je bilo povrnjeno',
'default_body' => 'Pozdravljeni, {customer_name},

Vaše plačilo je bilo povrnjeno.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Številka rezervacije: #{booking_id}',
  ),
  'payment_refunded_admin' => array (
'title' => 'Plačilo povrnjeno — skrbnik',
'description' => 'V čakalno vrsto za skrbnika ob vračilu plačila.',
'default_subject' => 'Plačilo povrnjeno za rezervacijo #{booking_id}',
'default_body' => 'Plačilo je bilo povrnjeno.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Številka rezervacije: #{booking_id}',
  ),
  'invoice_created_customer' => array (
'title' => 'Ustvarjen račun — stranka',
'description' => 'V čakalno vrsto za stranko ob ustvarjenem računu.',
'default_subject' => 'Račun za rezervacijo #{booking_id}',
'default_body' => 'Pozdravljeni, {customer_name},

Za vašo rezervacijo je bil ustvarjen račun.

Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Povzetek cene:
{price_summary}

Številka rezervacije: #{booking_id}',
  ),
  'invoice_created_admin' => array (
'title' => 'Ustvarjen račun — skrbnik',
'description' => 'V čakalno vrsto za skrbnika ob ustvarjenem računu.',
'default_subject' => 'Račun ustvarjen za rezervacijo #{booking_id}',
'default_body' => 'Račun je bil ustvarjen.

Stranka: {customer_name}
E-pošta: {customer_email}
Storitev: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Številka rezervacije: #{booking_id}',
  ),
  'magic_link_customer' => array (
'title' => 'Čarobna povezava — stranka',
'description' => 'Predloga za prihodnja e-poštna sporočila za prijavo strank.',
'default_subject' => 'Vaša povezava za prijavo',
'default_body' => 'Pozdravljeni, {customer_name},

Za prijavo v svoj račun uporabite to povezavo:

{magic_link}

Ta povezava bo kmalu potekla.',
  ),
  'contact_form_admin' => array (
'title' => 'Kontaktni obrazec — skrbnik',
'description' => 'Poslano skrbniku, ko obiskovalec odda kontaktni obrazec Slotera.',
'default_subject' => '[{site_name}] Novo kontaktno sporočilo',
'default_body' => 'Novo sporočilo iz kontaktnega obrazca.

Ime: {contact_name}
E-pošta: {contact_email}
Telefon: {contact_phone}
Zadeva: {contact_subject}
Sporočilo:
{contact_message}

Stran: {contact_page_title}
URL: {contact_page_url}
Oddano: {contact_submitted_at}
Jezik: {contact_locale}
IP: {contact_user_ip}
Uporabniški agent: {contact_user_agent}',
  ),
  'marketing_promo' => array (
'title' => 'Trženje — promocija',
'description' => 'Predloga za večkratno uporabo za promocijske kampanje, ponudbe in sporočila za ponovno pridobitev strank.',
'default_subject' => '{headline}',
'default_body' => 'Pozdravljeni, {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Posebna ponudba</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Koda vaše ponudbe</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · velja do {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'sk_SK' => array (
  'booking_created_customer' => 
  array (
'title' => 'Vytvorená rezervácia — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri vytvorení rezervácie.',
'default_subject' => 'Prijali sme vašu žiadosť o rezerváciu',
'default_body' => 'Dobrý deň, {customer_name},

Ďakujeme za rezerváciu. Vašu žiadosť sme prijali.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Stav rezervácie: {status_label}
Platba: {payment_status_label}

Súhrn ceny:
{price_summary}

Číslo rezervácie: #{booking_id}

Zrušiť rezerváciu: {cancellation_url}
Zmeniť termín rezervácie: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Nová rezervácia — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri vytvorení novej rezervácie.',
'default_subject' => 'Prijatá nová rezervácia',
'default_body' => 'Prijatá nová rezervácia.

Zákazník: {customer_name}
E-mail: {customer_email}
Telefón: {customer_phone}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Stav rezervácie: {status_label}
Platba: {payment_status_label}

Súhrn ceny:
{price_summary}

Číslo rezervácie: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Potvrdená rezervácia — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri potvrdení rezervácie.',
'default_subject' => 'Vaša rezervácia je potvrdená',
'default_body' => 'Dobrý deň, {customer_name},

Vaša rezervácia je potvrdená.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}

Zrušiť rezerváciu: {cancellation_url}
Zmeniť termín rezervácie: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Potvrdená rezervácia — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri potvrdení rezervácie.',
'default_subject' => 'Rezervácia potvrdená: #{booking_id}',
'default_body' => 'Rezervácia bola potvrdená.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervácie: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Pripomienka 24 h — zákazník',
'description' => 'Automaticky zaradené do frontu 24 hodín pred potvrdenou rezerváciou.',
'default_subject' => 'Pripomienka: vaša rezervácia je zajtra',
'default_body' => 'Dobrý deň, {customer_name},

Pripomíname vám nadchádzajúcu rezerváciu.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Zrušiť rezerváciu: {cancellation_url}
Zmeniť termín rezervácie: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Pripomienka 2 h — zákazník',
'description' => 'Automaticky zaradené do frontu 2 hodiny pred potvrdenou rezerváciou.',
'default_subject' => 'Pripomienka: vaša rezervácia sa čoskoro začína',
'default_body' => 'Dobrý deň, {customer_name},

Vaša rezervácia sa čoskoro začína.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Zrušená rezervácia — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri zrušení rezervácie.',
'default_subject' => 'Vaša rezervácia bola zrušená',
'default_body' => 'Dobrý deň, {customer_name},

Vaša rezervácia bola zrušená.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Zrušená rezervácia — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri zrušení rezervácie.',
'default_subject' => 'Rezervácia zrušená: #{booking_id}',
'default_body' => 'Rezervácia bola zrušená.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Presunutá rezervácia — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri zmene termínu rezervácie.',
'default_subject' => 'Termín vašej rezervácie bol zmenený',
'default_body' => 'Dobrý deň, {customer_name},

Termín vašej rezervácie bol zmenený.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}

Zrušiť rezerváciu: {cancellation_url}
Zmeniť termín rezervácie: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Presunutá rezervácia — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri zmene termínu rezervácie.',
'default_subject' => 'Termín rezervácie zmenený: #{booking_id}',
'default_body' => 'Termín rezervácie bol zmenený.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Stav rezervácie: {status_label}
Platba: {payment_status_label}
Číslo rezervácie: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Dokončená rezervácia — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri označení rezervácie ako dokončenej.',
'default_subject' => 'Ďakujeme, že ste si vybrali nás.',
'default_body' => 'Dobrý deň, {customer_name},

Ďakujeme, že ste si vybrali nás. Vaša rezervácia je teraz dokončená.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Dokončená rezervácia — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri označení rezervácie ako dokončenej.',
'default_subject' => 'Rezervácia dokončená: #{booking_id}',
'default_body' => 'Rezervácia bola dokončená.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervácie: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Zmenená služba — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri zmene služby alebo balíka rezervácie.',
'default_subject' => 'Služba vašej rezervácie bola zmenená',
'default_body' => 'Dobrý deň, {customer_name},

Služba vašej rezervácie bola zmenená.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Zmenená služba — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri zmene služby alebo balíka rezervácie.',
'default_subject' => 'Služba rezervácie zmenená: #{booking_id}',
'default_body' => 'Služba rezervácie bola zmenená.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervácie: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Čakajúca platba — zákazník',
'description' => 'Zaradené do frontu pre zákazníka, keď platba čaká alebo vyžaduje akciu.',
'default_subject' => 'Platba za vašu rezerváciu čaká na spracovanie',
'default_body' => 'Dobrý deň, {customer_name},

Platba za vašu rezerváciu čaká na spracovanie.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}

Súhrn ceny:
{price_summary}

Číslo rezervácie: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Čakajúca platba — administrátor',
'description' => 'Zaradené do frontu pre administrátora, keď platba čaká alebo vyžaduje akciu.',
'default_subject' => 'Platba za rezerváciu #{booking_id} čaká na spracovanie',
'default_body' => 'Platba čaká na spracovanie.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}

Súhrn ceny:
{price_summary}

Číslo rezervácie: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Potvrdenie platby — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri potvrdení platby.',
'default_subject' => 'Platba prijatá',
'default_body' => 'Dobrý deň, {customer_name},

Prijali sme vašu platbu.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}

Súhrn ceny:
{price_summary}

Číslo rezervácie: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Potvrdenie platby — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri potvrdení platby.',
'default_subject' => 'Platba za rezerváciu #{booking_id} prijatá',
'default_body' => 'Platba bola prijatá.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}

Súhrn ceny:
{price_summary}

Číslo rezervácie: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Neúspešná platba — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri zlyhaní platby.',
'default_subject' => 'Platba zlyhala',
'default_body' => 'Dobrý deň, {customer_name},

Vašu platbu sa nepodarilo dokončiť.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Neúspešná platba — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri zlyhaní platby.',
'default_subject' => 'Platba za rezerváciu #{booking_id} zlyhala',
'default_body' => 'Platba zlyhala.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}
Číslo rezervácie: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Vrátená platba — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri vrátení platby.',
'default_subject' => 'Vaša platba bola vrátená',
'default_body' => 'Dobrý deň, {customer_name},

Vaša platba bola vrátená.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervácie: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Vrátená platba — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri vrátení platby.',
'default_subject' => 'Platba za rezerváciu #{booking_id} bola vrátená',
'default_body' => 'Platba bola vrátená.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervácie: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Vytvorená faktúra — zákazník',
'description' => 'Zaradené do frontu pre zákazníka pri vytvorení faktúry.',
'default_subject' => 'Faktúra k rezervácii #{booking_id}',
'default_body' => 'Dobrý deň, {customer_name},

Pre vašu rezerváciu bola vytvorená faktúra.

Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}

Súhrn ceny:
{price_summary}

Číslo rezervácie: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Vytvorená faktúra — administrátor',
'description' => 'Zaradené do frontu pre administrátora pri vytvorení faktúry.',
'default_subject' => 'Faktúra k rezervácii #{booking_id} vytvorená',
'default_body' => 'Bola vytvorená faktúra.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Dátum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervácie: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Prihlasovací odkaz — zákazník',
'description' => 'Šablóna pre budúce e-maily s prihlásením zákazníka.',
'default_subject' => 'Váš prihlasovací odkaz',
'default_body' => 'Dobrý deň, {customer_name},

Na prihlásenie do účtu použite tento odkaz:

{magic_link}

Platnosť tohto odkazu čoskoro vyprší.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Kontaktný formulár — administrátor',
'description' => 'Odoslané administrátorovi, keď návštevník odošle kontaktný formulár Slotera.',
'default_subject' => '[{site_name}] Nová kontaktná správa',
'default_body' => 'Nová správa z kontaktného formulára.

Meno: {contact_name}
E-mail: {contact_email}
Telefón: {contact_phone}
Predmet: {contact_subject}
Správa:
{contact_message}

Stránka: {contact_page_title}
URL: {contact_page_url}
Odoslané: {contact_submitted_at}
Jazyk: {contact_locale}
IP: {contact_user_ip}
Používateľský agent: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Marketing — propagácia',
'description' => 'Opakovane použiteľná marketingová šablóna pre propagačné kampane, ponuky a e-maily na opätovné získanie zákazníkov.',
'default_subject' => '{headline}',
'default_body' => 'Dobrý deň, {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Špeciálna ponuka</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Váš zľavový kód</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · platí do {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'ro_RO' => [
            'booking_created_customer' => [
                'title' => 'Rezervare creată — client',
                'description' => 'Se adaugă în coadă pentru client când este creată o rezervare.',
                'default_subject' => 'Am primit solicitarea dvs. de rezervare',
                'default_body' => 'Bună ziua, {customer_name},

Vă mulțumim pentru rezervare. Rezervarea dvs. a fost înregistrată cu succes.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Starea rezervării: {status_label}
Plată: {payment_status_label}

Rezumatul prețului:
{price_summary}

Numărul rezervării: #{booking_id}

Anulați rezervarea: {cancellation_url}
Reprogramați rezervarea: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Rezervare nouă — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când este creată o rezervare nouă.',
                'default_subject' => 'A fost primită o rezervare nouă',
                'default_body' => 'A fost primită o rezervare nouă.

Client: {customer_name}
E-mail: {customer_email}
Telefon: {customer_phone}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Starea rezervării: {status_label}
Plată: {payment_status_label}

Rezumatul prețului:
{price_summary}

Numărul rezervării: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Rezervare confirmată — client',
                'description' => 'Se adaugă în coadă pentru client când o rezervare este confirmată.',
                'default_subject' => 'Rezervarea dvs. este confirmată',
                'default_body' => 'Bună ziua, {customer_name},

Rezervarea dvs. este confirmată.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}

Anulați rezervarea: {cancellation_url}
Reprogramați rezervarea: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Rezervare confirmată — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când o rezervare este confirmată.',
                'default_subject' => 'Rezervare confirmată: #{booking_id}',
                'default_body' => 'O rezervare a fost confirmată.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numărul rezervării: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Memento cu 24 de ore înainte — client',
                'description' => 'Se adaugă automat în coadă cu 24 de ore înaintea unei rezervări confirmate.',
                'default_subject' => 'Memento: rezervarea dvs. este mâine',
                'default_body' => 'Bună ziua, {customer_name},

Acesta este un memento pentru rezervarea dvs. viitoare.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Anulați rezervarea: {cancellation_url}
Reprogramați rezervarea: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Memento cu 2 ore înainte — client',
                'description' => 'Se adaugă automat în coadă cu 2 ore înaintea unei rezervări confirmate.',
                'default_subject' => 'Memento: rezervarea dvs. începe în curând',
                'default_body' => 'Bună ziua, {customer_name},

Rezervarea dvs. începe în curând.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Rezervare anulată — client',
                'description' => 'Se adaugă în coadă pentru client când o rezervare este anulată.',
                'default_subject' => 'Rezervarea dvs. a fost anulată',
                'default_body' => 'Bună ziua, {customer_name},

Rezervarea dvs. a fost anulată.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Rezervare anulată — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când o rezervare este anulată.',
                'default_subject' => 'Rezervare anulată: #{booking_id}',
                'default_body' => 'O rezervare a fost anulată.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Rezervare reprogramată — client',
                'description' => 'Se adaugă în coadă pentru client când o rezervare este reprogramată.',
                'default_subject' => 'Rezervarea dvs. a fost reprogramată',
                'default_body' => 'Bună ziua, {customer_name},

Rezervarea dvs. a fost reprogramată.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}

Anulați rezervarea: {cancellation_url}
Reprogramați rezervarea: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Rezervare reprogramată — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când o rezervare este reprogramată.',
                'default_subject' => 'Rezervare reprogramată: #{booking_id}',
                'default_body' => 'O rezervare a fost reprogramată.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Starea rezervării: {status_label}
Plată: {payment_status_label}
Numărul rezervării: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Rezervare finalizată — client',
                'description' => 'Se adaugă în coadă pentru client când o rezervare este marcată ca finalizată.',
                'default_subject' => 'Vă mulțumim că ne-ați ales.',
                'default_body' => 'Bună ziua, {customer_name},

Vă mulțumim că ne-ați ales. Rezervarea dvs. este acum finalizată.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Rezervare finalizată — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când o rezervare este marcată ca finalizată.',
                'default_subject' => 'Rezervare finalizată: #{booking_id}',
                'default_body' => 'O rezervare a fost finalizată.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numărul rezervării: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Serviciu modificat — client',
                'description' => 'Se adaugă în coadă pentru client când serviciul sau pachetul rezervării este modificat.',
                'default_subject' => 'Serviciul rezervării dvs. a fost modificat',
                'default_body' => 'Bună ziua, {customer_name},

Serviciul rezervării dvs. a fost modificat.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Serviciu modificat — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când serviciul sau pachetul rezervării este modificat.',
                'default_subject' => 'Serviciul rezervării a fost modificat: #{booking_id}',
                'default_body' => 'Serviciul unei rezervări a fost modificat.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numărul rezervării: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Plată în așteptare — client',
                'description' => 'Se adaugă în coadă pentru client când plata este în așteptare sau necesită o acțiune.',
                'default_subject' => 'Plata rezervării dvs. este în așteptare',
                'default_body' => 'Bună ziua, {customer_name},

Plata rezervării dvs. este în așteptare.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Plată: {payment_status_label}

Rezumatul prețului:
{price_summary}

Numărul rezervării: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Plată în așteptare — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când plata este în așteptare sau necesită o acțiune.',
                'default_subject' => 'Plată în așteptare pentru rezervarea #{booking_id}',
                'default_body' => 'Plata este în așteptare.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Plată: {payment_status_label}

Rezumatul prețului:
{price_summary}

Numărul rezervării: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Confirmare plată — client',
                'description' => 'Se adaugă în coadă pentru client când plata este confirmată.',
                'default_subject' => 'Plata a fost primită',
                'default_body' => 'Bună ziua, {customer_name},

Am primit plata dvs.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Plată: {payment_status_label}

Rezumatul prețului:
{price_summary}

Numărul rezervării: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Confirmare plată — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când plata este confirmată.',
                'default_subject' => 'Plată primită pentru rezervarea #{booking_id}',
                'default_body' => 'Plata a fost primită.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Plată: {payment_status_label}

Rezumatul prețului:
{price_summary}

Numărul rezervării: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Plată eșuată — client',
                'description' => 'Se adaugă în coadă pentru client când plata eșuează.',
                'default_subject' => 'Plata nu a reușit',
                'default_body' => 'Bună ziua, {customer_name},

Plata dvs. nu a putut fi finalizată.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Plată eșuată — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când plata eșuează.',
                'default_subject' => 'Plata pentru rezervarea #{booking_id} nu a reușit',
                'default_body' => 'Plata nu a reușit.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Plată: {payment_status_label}
Numărul rezervării: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Plată rambursată — client',
                'description' => 'Se adaugă în coadă pentru client când plata este rambursată.',
                'default_subject' => 'Plata rezervării dvs. a fost rambursată',
                'default_body' => 'Bună ziua, {customer_name},

Plata rezervării dvs. a fost rambursată.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numărul rezervării: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Plată rambursată — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când plata este rambursată.',
                'default_subject' => 'Plata rezervării #{booking_id} a fost rambursată',
                'default_body' => 'Plata a fost rambursată.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numărul rezervării: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Factură creată — client',
                'description' => 'Se adaugă în coadă pentru client când este creată o factură.',
                'default_subject' => 'Factura pentru rezervarea #{booking_id}',
                'default_body' => 'Bună ziua, {customer_name},

A fost creată o factură pentru rezervarea dvs.

Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Rezumatul prețului:
{price_summary}

Numărul rezervării: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Factură creată — administrator',
                'description' => 'Se adaugă în coadă pentru administrator când este creată o factură.',
                'default_subject' => 'Factura pentru rezervarea #{booking_id} a fost creată',
                'default_body' => 'A fost creată o factură.

Client: {customer_name}
E-mail: {customer_email}
Serviciu: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numărul rezervării: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Link de autentificare — client',
                'description' => 'Șablon pentru viitoarele e-mailuri de autentificare ale clienților.',
                'default_subject' => 'Linkul dvs. de autentificare',
                'default_body' => 'Bună ziua, {customer_name},

Folosiți acest link pentru a vă autentifica în cont:

{magic_link}

Acest link va expira în curând.',
            ],
            'contact_form_admin' => [
                'title' => 'Formular de contact — administrator',
                'description' => 'Se trimite administratorului când un vizitator transmite formularul de contact Slotera.',
                'default_subject' => '[{site_name}] Mesaj nou de contact',
                'default_body' => 'Mesaj nou din formularul de contact.

Nume: {contact_name}
E-mail: {contact_email}
Telefon: {contact_phone}
Subiect: {contact_subject}
Mesaj:
{contact_message}

Pagină: {contact_page_title}
URL: {contact_page_url}
Trimis la: {contact_submitted_at}
Limbă: {contact_locale}
IP: {contact_user_ip}
Agent utilizator: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Marketing — promoție',
                'description' => 'Șablon de marketing reutilizabil pentru campanii promoționale, oferte și e-mailuri de reactivare.',
                'default_subject' => '{headline}',
                'default_body' => 'Bună ziua, {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Ofertă specială</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Codul dvs. promoțional</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · valabil până la {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'pt_PT' => [
            'booking_created_customer' => [
                'title' => 'Reserva criada — cliente',
                'description' => 'Colocado na fila para o cliente quando é criada uma reserva.',
                'default_subject' => 'Recebemos o seu pedido de reserva',
                'default_body' => 'Olá {customer_name},

Obrigado pela sua reserva. Recebemos o seu pedido.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Estado: {status_label}
Pagamento: {payment_status_label}

Resumo do preço:
{price_summary}

Número da reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reagendar reserva: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Nova reserva — administrador',
                'description' => 'Colocado na fila para o administrador quando é criada uma nova reserva.',
                'default_subject' => 'Nova reserva recebida',
                'default_body' => 'Foi recebida uma nova reserva.

Cliente: {customer_name}
E-mail: {customer_email}
Telefone: {customer_phone}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Estado: {status_label}
Pagamento: {payment_status_label}

Resumo do preço:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Reserva confirmada — cliente',
                'description' => 'Colocado na fila para o cliente quando uma reserva é confirmada.',
                'default_subject' => 'A sua reserva está confirmada',
                'default_body' => 'Olá {customer_name},

A sua reserva está confirmada.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reagendar reserva: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Reserva confirmada — administrador',
                'description' => 'Colocado na fila para o administrador quando uma reserva é confirmada.',
                'default_subject' => 'Reserva confirmada: #{booking_id}',
                'default_body' => 'Uma reserva foi confirmada.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Lembrete de 24 h — cliente',
                'description' => 'Colocado automaticamente na fila 24 horas antes de uma reserva confirmada.',
                'default_subject' => 'Lembrete: a sua reserva é amanhã',
                'default_body' => 'Olá {customer_name},

Este é um lembrete da sua próxima reserva.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Cancelar reserva: {cancellation_url}
Reagendar reserva: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Lembrete de 2 h — cliente',
                'description' => 'Colocado automaticamente na fila 2 horas antes de uma reserva confirmada.',
                'default_subject' => 'Lembrete: a sua reserva começa em breve',
                'default_body' => 'Olá {customer_name},

A sua reserva começa em breve.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Reserva cancelada — cliente',
                'description' => 'Colocado na fila para o cliente quando uma reserva é cancelada.',
                'default_subject' => 'A sua reserva foi cancelada',
                'default_body' => 'Olá {customer_name},

A sua reserva foi cancelada.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Reserva cancelada — administrador',
                'description' => 'Colocado na fila para o administrador quando uma reserva é cancelada.',
                'default_subject' => 'Reserva cancelada: #{booking_id}',
                'default_body' => 'Uma reserva foi cancelada.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Reserva reagendada — cliente',
                'description' => 'Colocado na fila para o cliente quando uma reserva é reagendada.',
                'default_subject' => 'A sua reserva foi reagendada',
                'default_body' => 'Olá {customer_name},

A sua reserva foi reagendada.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reagendar reserva: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Reserva reagendada — administrador',
                'description' => 'Colocado na fila para o administrador quando uma reserva é reagendada.',
                'default_subject' => 'Reserva reagendada: #{booking_id}',
                'default_body' => 'Uma reserva foi reagendada.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Estado: {status_label}
Pagamento: {payment_status_label}
Número da reserva: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Reserva concluída — cliente',
                'description' => 'Colocado na fila para o cliente quando uma reserva é marcada como concluída.',
                'default_subject' => 'Agradecemos por nos escolher.',
                'default_body' => 'Olá {customer_name},

Agradecemos por nos escolher. A sua reserva está agora concluída.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Reserva concluída — administrador',
                'description' => 'Colocado na fila para o administrador quando uma reserva é marcada como concluída.',
                'default_subject' => 'Reserva concluída: #{booking_id}',
                'default_body' => 'Uma reserva foi concluída.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Serviço alterado — cliente',
                'description' => 'Colocado na fila para o cliente quando o serviço ou pacote da reserva é alterado.',
                'default_subject' => 'O serviço da sua reserva foi alterado',
                'default_body' => 'Olá {customer_name},

O serviço da sua reserva foi alterado.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Serviço alterado — administrador',
                'description' => 'Colocado na fila para o administrador quando o serviço ou pacote da reserva é alterado.',
                'default_subject' => 'Serviço da reserva alterado: #{booking_id}',
                'default_body' => 'O serviço de uma reserva foi alterado.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Pagamento pendente — cliente',
                'description' => 'Colocado na fila para o cliente quando o pagamento está pendente ou aguarda uma ação.',
                'default_subject' => 'O pagamento da sua reserva está pendente',
                'default_body' => 'Olá {customer_name},

O pagamento da sua reserva está pendente.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Pagamento: {payment_status_label}

Resumo do preço:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Pagamento pendente — administrador',
                'description' => 'Colocado na fila para o administrador quando o pagamento está pendente ou aguarda uma ação.',
                'default_subject' => 'Pagamento pendente para a reserva #{booking_id}',
                'default_body' => 'O pagamento está pendente.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Pagamento: {payment_status_label}

Resumo do preço:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Confirmação de pagamento — cliente',
                'description' => 'Colocado na fila para o cliente quando o pagamento é confirmado.',
                'default_subject' => 'Pagamento recebido',
                'default_body' => 'Olá {customer_name},

Recebemos o seu pagamento.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Pagamento: {payment_status_label}

Resumo do preço:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Confirmação de pagamento — administrador',
                'description' => 'Colocado na fila para o administrador quando o pagamento é confirmado.',
                'default_subject' => 'Pagamento recebido para a reserva #{booking_id}',
                'default_body' => 'Pagamento recebido.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Pagamento: {payment_status_label}

Resumo do preço:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Pagamento falhou — cliente',
                'description' => 'Colocado na fila para o cliente quando o pagamento falha.',
                'default_subject' => 'O pagamento falhou',
                'default_body' => 'Olá {customer_name},

Não foi possível concluir o seu pagamento.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Pagamento falhou — administrador',
                'description' => 'Colocado na fila para o administrador quando o pagamento falha.',
                'default_subject' => 'O pagamento da reserva #{booking_id} falhou',
                'default_body' => 'O pagamento falhou.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Pagamento: {payment_status_label}
Número da reserva: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Pagamento reembolsado — cliente',
                'description' => 'Colocado na fila para o cliente quando o pagamento é reembolsado.',
                'default_subject' => 'O seu pagamento foi reembolsado',
                'default_body' => 'Olá {customer_name},

O seu pagamento foi reembolsado.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Pagamento reembolsado — administrador',
                'description' => 'Colocado na fila para o administrador quando o pagamento é reembolsado.',
                'default_subject' => 'Pagamento reembolsado para a reserva #{booking_id}',
                'default_body' => 'Pagamento reembolsado.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Fatura criada — cliente',
                'description' => 'Colocado na fila para o cliente quando é criada uma fatura.',
                'default_subject' => 'Fatura da reserva #{booking_id}',
                'default_body' => 'Olá {customer_name},

Foi criada uma fatura para a sua reserva.

Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}

Resumo do preço:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Fatura criada — administrador',
                'description' => 'Colocado na fila para o administrador quando é criada uma fatura.',
                'default_subject' => 'Fatura criada para a reserva #{booking_id}',
                'default_body' => 'Foi criada uma fatura.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Hora: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Ligação mágica — cliente',
                'description' => 'Modelo para futuras mensagens de início de sessão do cliente.',
                'default_subject' => 'A sua ligação de início de sessão',
                'default_body' => 'Olá {customer_name},

Utilize esta ligação para iniciar sessão na sua conta:

{magic_link}

Esta ligação expira em breve.',
            ],
            'contact_form_admin' => [
                'title' => 'Formulário de contacto — administrador',
                'description' => 'Enviado ao administrador quando um visitante submete o formulário de contacto do Slotera.',
                'default_subject' => '[{site_name}] Nova mensagem de contacto',
                'default_body' => 'Nova mensagem do formulário de contacto.

Nome: {contact_name}
E-mail: {contact_email}
Telefone: {contact_phone}
Assunto: {contact_subject}
Mensagem:
{contact_message}

Página: {contact_page_title}
URL: {contact_page_url}
Enviado em: {contact_submitted_at}
Idioma: {contact_locale}
IP: {contact_user_ip}
Agente do utilizador: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Marketing — promoção',
                'description' => 'Modelo de marketing reutilizável para campanhas promocionais, ofertas e mensagens de recuperação de clientes.',
                'default_subject' => '{headline}',
                'default_body' => 'Olá {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Oferta especial</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">O seu código promocional</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · válido até {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'cs_CZ' => array (
  'booking_created_customer' => 
  array (
'title' => 'Vytvořená rezervace — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při vytvoření rezervace.',
'default_subject' => 'Přijali jsme vaši žádost o rezervaci',
'default_body' => 'Dobrý den, {customer_name},

Děkujeme za rezervaci. Vaši žádost jsme přijali.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Stav rezervace: {status_label}
Platba: {payment_status_label}

Souhrn ceny:
{price_summary}

Číslo rezervace: #{booking_id}

Zrušit rezervaci: {cancellation_url}
Změnit termín rezervace: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Nová rezervace — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při vytvoření nové rezervace.',
'default_subject' => 'Přijata nová rezervace',
'default_body' => 'Přijata nová rezervace.

Zákazník: {customer_name}
E-mail: {customer_email}
Telefon: {customer_phone}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Stav rezervace: {status_label}
Platba: {payment_status_label}

Souhrn ceny:
{price_summary}

Číslo rezervace: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Potvrzená rezervace — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při potvrzení rezervace.',
'default_subject' => 'Vaše rezervace je potvrzena',
'default_body' => 'Dobrý den, {customer_name},

Vaše rezervace je potvrzena.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}

Zrušit rezervaci: {cancellation_url}
Změnit termín rezervace: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Potvrzená rezervace — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při potvrzení rezervace.',
'default_subject' => 'Rezervace potvrzena: #{booking_id}',
'default_body' => 'Rezervace byla potvrzena.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervace: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Připomenutí 24 h — zákazník',
'description' => 'Automaticky zařazeno do fronty 24 hodin před potvrzenou rezervací.',
'default_subject' => 'Připomenutí: vaše rezervace je zítra',
'default_body' => 'Dobrý den, {customer_name},

Připomínáme vám nadcházející rezervaci.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Zrušit rezervaci: {cancellation_url}
Změnit termín rezervace: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Připomenutí 2 h — zákazník',
'description' => 'Automaticky zařazeno do fronty 2 hodiny před potvrzenou rezervací.',
'default_subject' => 'Připomenutí: vaše rezervace brzy začíná',
'default_body' => 'Dobrý den, {customer_name},

Vaše rezervace brzy začíná.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Zrušená rezervace — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při zrušení rezervace.',
'default_subject' => 'Vaše rezervace byla zrušena',
'default_body' => 'Dobrý den, {customer_name},

Vaše rezervace byla zrušena.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Zrušená rezervace — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při zrušení rezervace.',
'default_subject' => 'Rezervace zrušena: #{booking_id}',
'default_body' => 'Rezervace byla zrušena.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Přesunutá rezervace — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při změně termínu rezervace.',
'default_subject' => 'Termín vaší rezervace byl změněn',
'default_body' => 'Dobrý den, {customer_name},

Termín vaší rezervace byl změněn.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}

Zrušit rezervaci: {cancellation_url}
Změnit termín rezervace: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Přesunutá rezervace — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při změně termínu rezervace.',
'default_subject' => 'Termín rezervace změněn: #{booking_id}',
'default_body' => 'Termín rezervace byl změněn.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Stav rezervace: {status_label}
Platba: {payment_status_label}
Číslo rezervace: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Dokončená rezervace — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při označení rezervace jako dokončené.',
'default_subject' => 'Děkujeme, že jste si vybrali nás.',
'default_body' => 'Dobrý den, {customer_name},

Děkujeme, že jste si vybrali nás. Vaše rezervace je nyní dokončena.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Dokončená rezervace — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při označení rezervace jako dokončené.',
'default_subject' => 'Rezervace dokončena: #{booking_id}',
'default_body' => 'Rezervace byla dokončena.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervace: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Změněná služba — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při změně služby nebo balíčku rezervace.',
'default_subject' => 'Služba vaší rezervace byla změněna',
'default_body' => 'Dobrý den, {customer_name},

Služba vaší rezervace byla změněna.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Změněná služba — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při změně služby nebo balíčku rezervace.',
'default_subject' => 'Služba rezervace změněna: #{booking_id}',
'default_body' => 'Služba rezervace byla změněna.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervace: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Čekající platba — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka, když platba čeká nebo vyžaduje akci.',
'default_subject' => 'Platba za vaši rezervaci čeká na vyřízení',
'default_body' => 'Dobrý den, {customer_name},

Platba za vaši rezervaci čeká na vyřízení.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}

Souhrn ceny:
{price_summary}

Číslo rezervace: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Čekající platba — administrátor',
'description' => 'Zařazeno do fronty pro administrátora, když platba čeká nebo vyžaduje akci.',
'default_subject' => 'Platba za rezervaci #{booking_id} čeká na vyřízení',
'default_body' => 'Platba čeká na vyřízení.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}

Souhrn ceny:
{price_summary}

Číslo rezervace: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Potvrzení platby — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při potvrzení platby.',
'default_subject' => 'Platba přijata',
'default_body' => 'Dobrý den, {customer_name},

Obdrželi jsme vaši platbu.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}

Souhrn ceny:
{price_summary}

Číslo rezervace: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Potvrzení platby — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při potvrzení platby.',
'default_subject' => 'Platba za rezervaci #{booking_id} přijata',
'default_body' => 'Platba byla přijata.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}

Souhrn ceny:
{price_summary}

Číslo rezervace: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Neúspěšná platba — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při selhání platby.',
'default_subject' => 'Platba se nezdařila',
'default_body' => 'Dobrý den, {customer_name},

Vaši platbu se nepodařilo dokončit.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Neúspěšná platba — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při selhání platby.',
'default_subject' => 'Platba za rezervaci #{booking_id} se nezdařila',
'default_body' => 'Platba se nezdařila.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Platba: {payment_status_label}
Číslo rezervace: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Vrácená platba — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při vrácení platby.',
'default_subject' => 'Vaše platba byla vrácena',
'default_body' => 'Dobrý den, {customer_name},

Vaše platba byla vrácena.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Číslo rezervace: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Vrácená platba — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při vrácení platby.',
'default_subject' => 'Platba za rezervaci #{booking_id} byla vrácena',
'default_body' => 'Platba byla vrácena.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervace: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Vytvořená faktura — zákazník',
'description' => 'Zařazeno do fronty pro zákazníka při vytvoření faktury.',
'default_subject' => 'Faktura k rezervaci #{booking_id}',
'default_body' => 'Dobrý den, {customer_name},

Pro vaši rezervaci byla vytvořena faktura.

Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}

Souhrn ceny:
{price_summary}

Číslo rezervace: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Vytvořená faktura — administrátor',
'description' => 'Zařazeno do fronty pro administrátora při vytvoření faktury.',
'default_subject' => 'Faktura k rezervaci #{booking_id} vytvořena',
'default_body' => 'Byla vytvořena faktura.

Zákazník: {customer_name}
E-mail: {customer_email}
Služba: {package_title}
Datum: {booking_date}
Čas: {start_time} - {end_time}
Číslo rezervace: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Přihlašovací odkaz — zákazník',
'description' => 'Šablona pro budoucí e-maily s přihlášením zákazníka.',
'default_subject' => 'Váš přihlašovací odkaz',
'default_body' => 'Dobrý den, {customer_name},

Pro přihlášení k účtu použijte tento odkaz:

{magic_link}

Platnost tohoto odkazu brzy vyprší.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Kontaktní formulář — administrátor',
'description' => 'Odesláno administrátorovi, když návštěvník odešle kontaktní formulář Slotera.',
'default_subject' => '[{site_name}] Nová kontaktní zpráva',
'default_body' => 'Nová zpráva z kontaktního formuláře.

Jméno: {contact_name}
E-mail: {contact_email}
Telefon: {contact_phone}
Předmět: {contact_subject}
Zpráva:
{contact_message}

Stránka: {contact_page_title}
URL: {contact_page_url}
Odesláno: {contact_submitted_at}
Jazyk: {contact_locale}
IP: {contact_user_ip}
User agent: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Marketing — propagace',
'description' => 'Opakovaně použitelná marketingová šablona pro propagační kampaně, nabídky a návratové e-maily.',
'default_subject' => '{headline}',
'default_body' => 'Dobrý den, {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Speciální nabídka</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Váš slevový kód</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · platí do {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'pl_PL' => array (
  'booking_created_customer' => 
  array (
'title' => 'Utworzono rezerwację — klient',
'description' => 'Dodawane do kolejki dla klienta po utworzeniu rezerwacji.',
'default_subject' => 'Otrzymaliśmy Twoje zgłoszenie rezerwacji',
'default_body' => 'Dzień dobry {customer_name},

Dziękujemy za rezerwację. Otrzymaliśmy Twoje zgłoszenie.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Status rezerwacji: {status_label}
Płatność: {payment_status_label}

Podsumowanie ceny:
{price_summary}

Numer rezerwacji: #{booking_id}

Anuluj rezerwację: {cancellation_url}
Zmień termin rezerwacji: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Nowa rezerwacja — administrator',
'description' => 'Dodawane do kolejki dla administratora po utworzeniu nowej rezerwacji.',
'default_subject' => 'Otrzymano nową rezerwację',
'default_body' => 'Otrzymano nową rezerwację.

Klient: {customer_name}
E-mail: {customer_email}
Telefon: {customer_phone}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Status rezerwacji: {status_label}
Płatność: {payment_status_label}

Podsumowanie ceny:
{price_summary}

Numer rezerwacji: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Potwierdzono rezerwację — klient',
'description' => 'Dodawane do kolejki dla klienta po potwierdzeniu rezerwacji.',
'default_subject' => 'Twoja rezerwacja została potwierdzona',
'default_body' => 'Dzień dobry {customer_name},

Twoja rezerwacja została potwierdzona.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}

Anuluj rezerwację: {cancellation_url}
Zmień termin rezerwacji: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Potwierdzono rezerwację — administrator',
'description' => 'Dodawane do kolejki dla administratora po potwierdzeniu rezerwacji.',
'default_subject' => 'Potwierdzono rezerwację: #{booking_id}',
'default_body' => 'Rezerwacja została potwierdzona.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Numer rezerwacji: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Przypomnienie 24 godz. — klient',
'description' => 'Dodawane automatycznie do kolejki 24 godziny przed potwierdzoną rezerwacją.',
'default_subject' => 'Przypomnienie: Twoja rezerwacja jest jutro',
'default_body' => 'Dzień dobry {customer_name},

Przypominamy o zbliżającej się rezerwacji.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Anuluj rezerwację: {cancellation_url}
Zmień termin rezerwacji: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Przypomnienie 2 godz. — klient',
'description' => 'Dodawane automatycznie do kolejki 2 godziny przed potwierdzoną rezerwacją.',
'default_subject' => 'Przypomnienie: Twoja rezerwacja rozpocznie się wkrótce',
'default_body' => 'Dzień dobry {customer_name},

Twoja rezerwacja rozpocznie się wkrótce.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Anulowano rezerwację — klient',
'description' => 'Dodawane do kolejki dla klienta po anulowaniu rezerwacji.',
'default_subject' => 'Twoja rezerwacja została anulowana',
'default_body' => 'Dzień dobry {customer_name},

Twoja rezerwacja została anulowana.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Anulowano rezerwację — administrator',
'description' => 'Dodawane do kolejki dla administratora po anulowaniu rezerwacji.',
'default_subject' => 'Anulowano rezerwację: #{booking_id}',
'default_body' => 'Rezerwacja została anulowana.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Zmieniono termin rezerwacji — klient',
'description' => 'Dodawane do kolejki dla klienta po zmianie terminu rezerwacji.',
'default_subject' => 'Termin Twojej rezerwacji został zmieniony',
'default_body' => 'Dzień dobry {customer_name},

Termin Twojej rezerwacji został zmieniony.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}

Anuluj rezerwację: {cancellation_url}
Zmień termin rezerwacji: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Zmieniono termin rezerwacji — administrator',
'description' => 'Dodawane do kolejki dla administratora po zmianie terminu rezerwacji.',
'default_subject' => 'Zmieniono termin rezerwacji: #{booking_id}',
'default_body' => 'Termin rezerwacji został zmieniony.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Status rezerwacji: {status_label}
Płatność: {payment_status_label}
Numer rezerwacji: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Zakończono rezerwację — klient',
'description' => 'Dodawane do kolejki dla klienta po oznaczeniu rezerwacji jako zakończonej.',
'default_subject' => 'Dziękujemy, że wybrali Państwo nas.',
'default_body' => 'Dzień dobry {customer_name},

Dziękujemy, że wybrali Państwo nas. Twoja rezerwacja została zakończona.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Zakończono rezerwację — administrator',
'description' => 'Dodawane do kolejki dla administratora po oznaczeniu rezerwacji jako zakończonej.',
'default_subject' => 'Zakończono rezerwację: #{booking_id}',
'default_body' => 'Rezerwacja została zakończona.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Numer rezerwacji: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Zmieniono usługę — klient',
'description' => 'Dodawane do kolejki dla klienta po zmianie usługi lub pakietu rezerwacji.',
'default_subject' => 'Usługa w Twojej rezerwacji została zmieniona',
'default_body' => 'Dzień dobry {customer_name},

Usługa w Twojej rezerwacji została zmieniona.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Zmieniono usługę — administrator',
'description' => 'Dodawane do kolejki dla administratora po zmianie usługi lub pakietu rezerwacji.',
'default_subject' => 'Zmieniono usługę w rezerwacji: #{booking_id}',
'default_body' => 'Usługa w rezerwacji została zmieniona.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Numer rezerwacji: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Płatność oczekująca — klient',
'description' => 'Dodawane do kolejki dla klienta, gdy płatność oczekuje lub wymaga działania.',
'default_subject' => 'Płatność za Twoją rezerwację oczekuje',
'default_body' => 'Dzień dobry {customer_name},

Płatność za Twoją rezerwację oczekuje.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Płatność: {payment_status_label}

Podsumowanie ceny:
{price_summary}

Numer rezerwacji: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Płatność oczekująca — administrator',
'description' => 'Dodawane do kolejki dla administratora, gdy płatność oczekuje lub wymaga działania.',
'default_subject' => 'Płatność oczekuje dla rezerwacji #{booking_id}',
'default_body' => 'Płatność oczekuje.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Płatność: {payment_status_label}

Podsumowanie ceny:
{price_summary}

Numer rezerwacji: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Potwierdzenie płatności — klient',
'description' => 'Dodawane do kolejki dla klienta po potwierdzeniu płatności.',
'default_subject' => 'Otrzymaliśmy płatność',
'default_body' => 'Dzień dobry {customer_name},

Otrzymaliśmy Twoją płatność.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Płatność: {payment_status_label}

Podsumowanie ceny:
{price_summary}

Numer rezerwacji: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Potwierdzenie płatności — administrator',
'description' => 'Dodawane do kolejki dla administratora po potwierdzeniu płatności.',
'default_subject' => 'Otrzymano płatność za rezerwację #{booking_id}',
'default_body' => 'Otrzymano płatność.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Płatność: {payment_status_label}

Podsumowanie ceny:
{price_summary}

Numer rezerwacji: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Płatność nie powiodła się — klient',
'description' => 'Dodawane do kolejki dla klienta, gdy płatność nie powiedzie się.',
'default_subject' => 'Płatność nie powiodła się',
'default_body' => 'Dzień dobry {customer_name},

Nie udało się zrealizować Twojej płatności.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Płatność nie powiodła się — administrator',
'description' => 'Dodawane do kolejki dla administratora, gdy płatność nie powiedzie się.',
'default_subject' => 'Płatność nie powiodła się dla rezerwacji #{booking_id}',
'default_body' => 'Płatność nie powiodła się.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Płatność: {payment_status_label}
Numer rezerwacji: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Zwrócono płatność — klient',
'description' => 'Dodawane do kolejki dla klienta po zwrocie płatności.',
'default_subject' => 'Twoja płatność została zwrócona',
'default_body' => 'Dzień dobry {customer_name},

Twoja płatność została zwrócona.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Numer rezerwacji: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Zwrócono płatność — administrator',
'description' => 'Dodawane do kolejki dla administratora po zwrocie płatności.',
'default_subject' => 'Zwrócono płatność za rezerwację #{booking_id}',
'default_body' => 'Płatność została zwrócona.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Numer rezerwacji: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Utworzono fakturę — klient',
'description' => 'Dodawane do kolejki dla klienta po utworzeniu faktury.',
'default_subject' => 'Faktura za rezerwację #{booking_id}',
'default_body' => 'Dzień dobry {customer_name},

Utworzono fakturę za Twoją rezerwację.

Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}

Podsumowanie ceny:
{price_summary}

Numer rezerwacji: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Utworzono fakturę — administrator',
'description' => 'Dodawane do kolejki dla administratora po utworzeniu faktury.',
'default_subject' => 'Utworzono fakturę dla rezerwacji #{booking_id}',
'default_body' => 'Utworzono fakturę.

Klient: {customer_name}
E-mail: {customer_email}
Usługa: {package_title}
Data: {booking_date}
Godzina: {start_time} - {end_time}
Numer rezerwacji: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Link logowania — klient',
'description' => 'Szablon przyszłych wiadomości e-mail z linkiem logowania klienta.',
'default_subject' => 'Twój link logowania',
'default_body' => 'Dzień dobry {customer_name},

Użyj tego linku, aby zalogować się na swoje konto:

{magic_link}

Ten link wkrótce wygaśnie.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Formularz kontaktowy — administrator',
'description' => 'Wysyłane do administratora po przesłaniu formularza kontaktowego Slotera przez odwiedzającego.',
'default_subject' => '[{site_name}] Nowa wiadomość kontaktowa',
'default_body' => 'Nowa wiadomość z formularza kontaktowego.

Imię i nazwisko: {contact_name}
E-mail: {contact_email}
Telefon: {contact_phone}
Temat: {contact_subject}
Wiadomość:
{contact_message}

Strona: {contact_page_title}
URL: {contact_page_url}
Wysłano: {contact_submitted_at}
Język: {contact_locale}
IP: {contact_user_ip}
Agent użytkownika: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Marketing — promocja',
'description' => 'Szablon marketingowy wielokrotnego użytku do kampanii promocyjnych, ofert i wiadomości zachęcających klientów do powrotu.',
'default_subject' => '{headline}',
'default_body' => 'Dzień dobry {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Oferta specjalna</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Twój kod promocyjny</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · ważny do {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'pt_BR' => [
            'booking_created_customer' => [
                'title' => 'Reserva criada — cliente',
                'description' => 'Enviado para a fila para o cliente quando uma reserva é criada.',
                'default_subject' => 'Recebemos sua solicitação de reserva',
                'default_body' => 'Olá {customer_name},

Agradecemos pelsua reserva. Recebemos sua solicitação.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Situação: {status_label}
Pagamento: {payment_status_label}

Resumo de preços:
{price_summary}

Número da reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reagendar reserva: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Nova reserva — administrador',
                'description' => 'Enviado para a fila para o administrador quando uma reserva é criada uma nova reserva.',
                'default_subject' => 'Nova reserva recebida',
                'default_body' => 'Foi recebida uma nova reserva.

Cliente: {customer_name}
E-mail: {customer_email}
Telefone: {customer_phone}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Situação: {status_label}
Pagamento: {payment_status_label}

Resumo de preços:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Reserva confirmada — cliente',
                'description' => 'Enviado para a fila para o cliente quando uma reserva é confirmada.',
                'default_subject' => 'Sua reserva está confirmada',
                'default_body' => 'Olá {customer_name},

Sua reserva está confirmada.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reagendar reserva: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Reserva confirmada — administrador',
                'description' => 'Enviado para a fila para o administrador quando uma reserva é confirmada.',
                'default_subject' => 'Reserva confirmada: #{booking_id}',
                'default_body' => 'Uma reserva foi confirmada.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Lembrete de 24 h — cliente',
                'description' => 'Enviado automaticamente para a fila 24 horas antes de uma reserva confirmada.',
                'default_subject' => 'Lembrete: sua reserva é amanhã',
                'default_body' => 'Olá {customer_name},

Este é um lembrete da sua próxima reserva.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Cancelar reserva: {cancellation_url}
Reagendar reserva: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Lembrete de 2 h — cliente',
                'description' => 'Enviado automaticamente para a fila 2 horas antes de uma reserva confirmada.',
                'default_subject' => 'Lembrete: sua reserva começa em breve',
                'default_body' => 'Olá {customer_name},

Sua reserva começa em breve.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Reserva cancelada — cliente',
                'description' => 'Enviado para a fila para o cliente quando uma reserva é cancelada.',
                'default_subject' => 'Sua reserva foi cancelada',
                'default_body' => 'Olá {customer_name},

Sua reserva foi cancelada.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Reserva cancelada — administrador',
                'description' => 'Enviado para a fila para o administrador quando uma reserva é cancelada.',
                'default_subject' => 'Reserva cancelada: #{booking_id}',
                'default_body' => 'Uma reserva foi cancelada.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Reserva reagendada — cliente',
                'description' => 'Enviado para a fila para o cliente quando uma reserva é reagendada.',
                'default_subject' => 'Sua reserva foi reagendada',
                'default_body' => 'Olá {customer_name},

Sua reserva foi reagendada.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reagendar reserva: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Reserva reagendada — administrador',
                'description' => 'Enviado para a fila para o administrador quando uma reserva é reagendada.',
                'default_subject' => 'Reserva reagendada: #{booking_id}',
                'default_body' => 'Uma reserva foi reagendada.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Situação: {status_label}
Pagamento: {payment_status_label}
Número da reserva: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Reserva concluída — cliente',
                'description' => 'Enviado para a fila para o cliente quando uma reserva é marcada como concluída.',
                'default_subject' => 'Agradecemos por nos escolher.',
                'default_body' => 'Olá {customer_name},

Agradecemos por nos escolher. Sua reserva foi concluída.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Reserva concluída — administrador',
                'description' => 'Enviado para a fila para o administrador quando uma reserva é marcada como concluída.',
                'default_subject' => 'Reserva concluída: #{booking_id}',
                'default_body' => 'Uma reserva foi concluída.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Serviço alterado — cliente',
                'description' => 'Enviado para a fila para o cliente quando o serviço ou pacote da reserva é alterado.',
                'default_subject' => 'O serviço dsua reserva foi alterado',
                'default_body' => 'Olá {customer_name},

O serviço dsua reserva foi alterado.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Serviço alterado — administrador',
                'description' => 'Enviado para a fila para o administrador quando o serviço ou pacote da reserva é alterado.',
                'default_subject' => 'Serviço da reserva alterado: #{booking_id}',
                'default_body' => 'O serviço de uma reserva foi alterado.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Pagamento pendente — cliente',
                'description' => 'Enviado para a fila para o cliente quando o pagamento está pendente ou aguarda uma ação.',
                'default_subject' => 'O pagamento dsua reserva está pendente',
                'default_body' => 'Olá {customer_name},

O pagamento dsua reserva está pendente.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Pagamento: {payment_status_label}

Resumo de preços:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Pagamento pendente — administrador',
                'description' => 'Enviado para a fila para o administrador quando o pagamento está pendente ou aguarda uma ação.',
                'default_subject' => 'Pagamento pendente para a reserva #{booking_id}',
                'default_body' => 'O pagamento está pendente.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Pagamento: {payment_status_label}

Resumo de preços:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Confirmação de pagamento — cliente',
                'description' => 'Enviado para a fila para o cliente quando o pagamento é confirmado.',
                'default_subject' => 'Pagamento recebido',
                'default_body' => 'Olá {customer_name},

Recebemos seu pagamento.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Pagamento: {payment_status_label}

Resumo de preços:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Confirmação de pagamento — administrador',
                'description' => 'Enviado para a fila para o administrador quando o pagamento é confirmado.',
                'default_subject' => 'Pagamento recebido para a reserva #{booking_id}',
                'default_body' => 'Pagamento recebido.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Pagamento: {payment_status_label}

Resumo de preços:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Pagamento falhou — cliente',
                'description' => 'Enviado para a fila para o cliente quando o pagamento falha.',
                'default_subject' => 'O pagamento falhou',
                'default_body' => 'Olá {customer_name},

Não foi possível concluir seu pagamento.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Pagamento falhou — administrador',
                'description' => 'Enviado para a fila para o administrador quando o pagamento falha.',
                'default_subject' => 'O pagamento da reserva #{booking_id} falhou',
                'default_body' => 'O pagamento falhou.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Pagamento: {payment_status_label}
Número da reserva: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Pagamento reembolsado — cliente',
                'description' => 'Enviado para a fila para o cliente quando o pagamento é reembolsado.',
                'default_subject' => 'Seu pagamento foi reembolsado',
                'default_body' => 'Olá {customer_name},

Seu pagamento foi reembolsado.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Número da reserva: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Pagamento reembolsado — administrador',
                'description' => 'Enviado para a fila para o administrador quando o pagamento é reembolsado.',
                'default_subject' => 'Pagamento reembolsado para a reserva #{booking_id}',
                'default_body' => 'Pagamento reembolsado.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Fatura criada — cliente',
                'description' => 'Enviado para a fila para o cliente quando uma reserva é criada uma fatura.',
                'default_subject' => 'Fatura da reserva #{booking_id}',
                'default_body' => 'Olá {customer_name},

Foi criada uma fatura para sua reserva.

Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}

Resumo de preços:
{price_summary}

Número da reserva: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Fatura criada — administrador',
                'description' => 'Enviado para a fila para o administrador quando uma reserva é criada uma fatura.',
                'default_subject' => 'Fatura criada para a reserva #{booking_id}',
                'default_body' => 'Foi criada uma fatura.

Cliente: {customer_name}
E-mail: {customer_email}
Serviço: {package_title}
Data: {booking_date}
Horário: {start_time} - {end_time}
Número da reserva: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Link mágico — cliente',
                'description' => 'Modelo para futuras mensagens de login do cliente.',
                'default_subject' => 'A sua link de login',
                'default_body' => 'Olá {customer_name},

Utilize esta link para iniciar sessão em sua conta:

{magic_link}

Este link expira em breve.',
            ],
            'contact_form_admin' => [
                'title' => 'Formulário de contato — administrador',
                'description' => 'Enviado ao administrador quando um visitante envia o formulário de contato do Slotera.',
                'default_subject' => '[{site_name}] Nova mensagem de contato',
                'default_body' => 'Nova mensagem do formulário de contato.

Nome: {contact_name}
E-mail: {contact_email}
Telefone: {contact_phone}
Assunto: {contact_subject}
Mensagem:
{contact_message}

Página: {contact_page_title}
URL: {contact_page_url}
Enviado em: {contact_submitted_at}
Idioma: {contact_locale}
IP: {contact_user_ip}
Agente do usuário: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Marketing — promoção',
                'description' => 'Modelo de marketing reutilizável para campanhas promocionais, ofertas e mensagens de recuperação de clientes.',
                'default_subject' => '{headline}',
                'default_body' => 'Olá {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Oferta especial</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Seu código promocional</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · válido até {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'it_IT' => [
            'booking_created_customer' => [
                'title' => 'Prenotazione creata — cliente',
                'description' => 'Inserita in coda per il cliente quando viene creata una prenotazione.',
                'default_subject' => 'Abbiamo ricevuto la tua richiesta di prenotazione',
                'default_body' => 'Ciao {customer_name},

grazie per la tua prenotazione. Abbiamo ricevuto la tua richiesta.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Stato: {status_label}
Pagamento: {payment_status_label}

Riepilogo del prezzo:
{price_summary}

Numero di prenotazione: #{booking_id}

Annulla prenotazione: {cancellation_url}
Ripianifica prenotazione: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Nuova prenotazione — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando viene creata una nuova prenotazione.',
                'default_subject' => 'Nuova prenotazione ricevuta',
                'default_body' => 'È stata ricevuta una nuova prenotazione.

Cliente: {customer_name}
E-mail: {customer_email}
Telefono: {customer_phone}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Stato: {status_label}
Pagamento: {payment_status_label}

Riepilogo del prezzo:
{price_summary}

Numero di prenotazione: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Prenotazione confermata — cliente',
                'description' => 'Inserita in coda per il cliente quando una prenotazione viene confermata.',
                'default_subject' => 'La tua prenotazione è confermata',
                'default_body' => 'Ciao {customer_name},

la tua prenotazione è confermata.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}

Annulla prenotazione: {cancellation_url}
Ripianifica prenotazione: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Prenotazione confermata — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando una prenotazione viene confermata.',
                'default_subject' => 'Prenotazione confermata: #{booking_id}',
                'default_body' => 'Una prenotazione è stata confermata.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numero di prenotazione: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Promemoria 24 ore — cliente',
                'description' => 'Inserito automaticamente in coda 24 ore prima di una prenotazione confermata.',
                'default_subject' => 'Promemoria: la tua prenotazione è domani',
                'default_body' => 'Ciao {customer_name},

ti ricordiamo la tua prossima prenotazione.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Annulla prenotazione: {cancellation_url}
Ripianifica prenotazione: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Promemoria 2 ore — cliente',
                'description' => 'Inserito automaticamente in coda 2 ore prima di una prenotazione confermata.',
                'default_subject' => 'Promemoria: la tua prenotazione inizierà a breve',
                'default_body' => 'Ciao {customer_name},

la tua prenotazione inizierà a breve.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Prenotazione annullata — cliente',
                'description' => 'Inserita in coda per il cliente quando una prenotazione viene annullata.',
                'default_subject' => 'La tua prenotazione è stata annullata',
                'default_body' => 'Ciao {customer_name},

la tua prenotazione è stata annullata.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Prenotazione annullata — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando una prenotazione viene annullata.',
                'default_subject' => 'Prenotazione annullata: #{booking_id}',
                'default_body' => 'Una prenotazione è stata annullata.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Prenotazione riprogrammata — cliente',
                'description' => 'Inserita in coda per il cliente quando una prenotazione viene riprogrammata.',
                'default_subject' => 'La tua prenotazione è stata riprogrammata',
                'default_body' => 'Ciao {customer_name},

la tua prenotazione è stata riprogrammata.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}

Annulla prenotazione: {cancellation_url}
Ripianifica prenotazione: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Prenotazione riprogrammata — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando una prenotazione viene riprogrammata.',
                'default_subject' => 'Prenotazione riprogrammata: #{booking_id}',
                'default_body' => 'Una prenotazione è stata riprogrammata.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Stato: {status_label}
Pagamento: {payment_status_label}
Numero di prenotazione: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Prenotazione completata — cliente',
                'description' => 'Inserita in coda per il cliente quando una prenotazione viene contrassegnata come completata.',
                'default_subject' => 'Grazie per averci scelto.',
                'default_body' => 'Ciao {customer_name},

grazie per averci scelto. La prenotazione è ora completata.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Prenotazione completata — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando una prenotazione viene contrassegnata come completata.',
                'default_subject' => 'Prenotazione completata: #{booking_id}',
                'default_body' => 'Una prenotazione è stata completata.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numero di prenotazione: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Servizio modificato — cliente',
                'description' => 'Inserita in coda per il cliente quando viene modificato il servizio o pacchetto della prenotazione.',
                'default_subject' => 'Il servizio della tua prenotazione è stato modificato',
                'default_body' => 'Ciao {customer_name},

il servizio della tua prenotazione è stato modificato.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Servizio modificato — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando viene modificato il servizio o pacchetto della prenotazione.',
                'default_subject' => 'Servizio della prenotazione modificato: #{booking_id}',
                'default_body' => 'Il servizio di una prenotazione è stato modificato.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numero di prenotazione: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Pagamento in sospeso — cliente',
                'description' => 'Inserita in coda per il cliente quando il pagamento è in sospeso o richiede un’azione.',
                'default_subject' => 'Il pagamento della tua prenotazione è in sospeso',
                'default_body' => 'Ciao {customer_name},

il pagamento della tua prenotazione è in sospeso.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Pagamento: {payment_status_label}

Riepilogo del prezzo:
{price_summary}

Numero di prenotazione: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Pagamento in sospeso — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando il pagamento è in sospeso o richiede un’azione.',
                'default_subject' => 'Pagamento in sospeso per la prenotazione #{booking_id}',
                'default_body' => 'Il pagamento di una prenotazione è in sospeso.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Pagamento: {payment_status_label}

Riepilogo del prezzo:
{price_summary}

Numero di prenotazione: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Pagamento ricevuto — cliente',
                'description' => 'Inserita in coda per il cliente quando viene ricevuto un pagamento.',
                'default_subject' => 'Pagamento ricevuto per la tua prenotazione',
                'default_body' => 'Ciao {customer_name},

abbiamo ricevuto il tuo pagamento.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Pagamento: {payment_status_label}

Riepilogo del prezzo:
{price_summary}

Numero di prenotazione: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Pagamento ricevuto — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando viene ricevuto un pagamento.',
                'default_subject' => 'Pagamento ricevuto per la prenotazione #{booking_id}',
                'default_body' => 'È stato ricevuto un pagamento.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Pagamento: {payment_status_label}

Riepilogo del prezzo:
{price_summary}

Numero di prenotazione: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Pagamento non riuscito — cliente',
                'description' => 'Inserita in coda per il cliente quando un pagamento non riesce.',
                'default_subject' => 'Pagamento non riuscito',
                'default_body' => 'Ciao {customer_name},

non è stato possibile completare il pagamento.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Pagamento non riuscito — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando un pagamento non riesce.',
                'default_subject' => 'Pagamento non riuscito per la prenotazione #{booking_id}',
                'default_body' => 'Il pagamento non è riuscito.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Pagamento: {payment_status_label}
Numero di prenotazione: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Pagamento rimborsato — cliente',
                'description' => 'Inserita in coda per il cliente quando un pagamento viene rimborsato.',
                'default_subject' => 'Il tuo pagamento è stato rimborsato',
                'default_body' => 'Ciao {customer_name},

il tuo pagamento è stato rimborsato.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Numero di prenotazione: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Pagamento rimborsato — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando un pagamento viene rimborsato.',
                'default_subject' => 'Pagamento rimborsato per la prenotazione #{booking_id}',
                'default_body' => 'Il pagamento è stato rimborsato.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numero di prenotazione: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Fattura creata — cliente',
                'description' => 'Inserita in coda per il cliente quando viene creata una fattura.',
                'default_subject' => 'Fattura per la prenotazione #{booking_id}',
                'default_body' => 'Ciao {customer_name},

è stata creata una fattura per la tua prenotazione.

Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}

Riepilogo del prezzo:
{price_summary}

Numero di prenotazione: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Fattura creata — amministratore',
                'description' => 'Inserita in coda per l’amministratore quando viene creata una fattura.',
                'default_subject' => 'Fattura creata per la prenotazione #{booking_id}',
                'default_body' => 'È stata creata una fattura.

Cliente: {customer_name}
E-mail: {customer_email}
Servizio: {package_title}
Data: {booking_date}
Ora: {start_time} - {end_time}
Numero di prenotazione: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Link di accesso — cliente',
                'description' => 'Modello per le e-mail di accesso del cliente.',
                'default_subject' => 'Il tuo link di accesso',
                'default_body' => 'Ciao {customer_name},

usa questo link per accedere al tuo account:

{magic_link}

Il link scadrà a breve.',
            ],
            'contact_form_admin' => [
                'title' => 'Modulo di contatto — amministratore',
                'description' => 'Inviata all’amministratore quando un visitatore invia il modulo di contatto Slotera.',
                'default_subject' => '[{site_name}] Nuovo messaggio di contatto',
                'default_body' => 'Nuovo messaggio dal modulo di contatto.

Nome: {contact_name}
E-mail: {contact_email}
Telefono: {contact_phone}
Oggetto: {contact_subject}
Messaggio:
{contact_message}

Pagina: {contact_page_title}
URL: {contact_page_url}
Inviato il: {contact_submitted_at}
Lingua: {contact_locale}
IP: {contact_user_ip}
Agente utente: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Marketing — promozione',
                'description' => 'Modello marketing riutilizzabile per campagne promozionali, offerte e messaggi di ritorno.',
                'default_subject' => '{headline}',
                'default_body' => 'Ciao {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Offerta speciale</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Il tuo codice promozionale</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · valido fino al {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'es_ES' => [
            'booking_created_customer' => [
                'title' => 'Reserva creada — cliente',
                'description' => 'Se añade a la cola para el cliente cuando se crea una reserva.',
                'default_subject' => 'Hemos recibido tu solicitud de reserva',
                'default_body' => 'Hola {customer_name},

Gracias por tu reserva. Hemos recibido tu solicitud.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Estado: {status_label}
Pago: {payment_status_label}

Resumen del precio:
{price_summary}

Número de reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reprogramar reserva: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Nueva reserva — administrador',
                'description' => 'Se añade a la cola para el administrador cuando se crea una nueva reserva.',
                'default_subject' => 'Nueva reserva recibida',
                'default_body' => 'Se ha recibido una nueva reserva.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Teléfono: {customer_phone}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Estado: {status_label}
Pago: {payment_status_label}

Resumen del precio:
{price_summary}

Número de reserva: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Reserva confirmada — cliente',
                'description' => 'Se añade a la cola para el cliente cuando se confirma una reserva.',
                'default_subject' => 'Tu reserva está confirmada',
                'default_body' => 'Hola {customer_name},

Tu reserva está confirmada.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reprogramar reserva: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Reserva confirmada — administrador',
                'description' => 'Se añade a la cola para el administrador cuando se confirma una reserva.',
                'default_subject' => 'Reserva confirmada: #{booking_id}',
                'default_body' => 'Se ha confirmado una reserva.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Número de reserva: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Recordatorio 24 h — cliente',
                'description' => 'Se añade automáticamente a la cola 24 horas antes de una reserva confirmada.',
                'default_subject' => 'Recordatorio: tu reserva es mañana',
                'default_body' => 'Hola {customer_name},

Este es un recordatorio de tu próxima reserva.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Cancelar reserva: {cancellation_url}
Reprogramar reserva: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Recordatorio 2 h — cliente',
                'description' => 'Se añade automáticamente a la cola 2 horas antes de una reserva confirmada.',
                'default_subject' => 'Recordatorio: tu reserva comienza pronto',
                'default_body' => 'Hola {customer_name},

Tu reserva comienza pronto.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Reserva cancelada — cliente',
                'description' => 'Se añade a la cola para el cliente cuando se cancela una reserva.',
                'default_subject' => 'Tu reserva ha sido cancelada',
                'default_body' => 'Hola {customer_name},

Tu reserva ha sido cancelada.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Reserva cancelada — administrador',
                'description' => 'Se añade a la cola para el administrador cuando se cancela una reserva.',
                'default_subject' => 'Reserva cancelada: #{booking_id}',
                'default_body' => 'Se ha cancelado una reserva.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Reserva reprogramada — cliente',
                'description' => 'Se añade a la cola para el cliente cuando se reprograma una reserva.',
                'default_subject' => 'Tu reserva ha sido reprogramada',
                'default_body' => 'Hola {customer_name},

Tu reserva ha sido reprogramada.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}

Cancelar reserva: {cancellation_url}
Reprogramar reserva: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Reserva reprogramada — administrador',
                'description' => 'Se añade a la cola para el administrador cuando se reprograma una reserva.',
                'default_subject' => 'Reserva reprogramada: #{booking_id}',
                'default_body' => 'Se ha reprogramado una reserva.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Estado: {status_label}
Pago: {payment_status_label}
Número de reserva: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Reserva completada — cliente',
                'description' => 'Se añade a la cola para el cliente cuando una reserva se marca como completada.',
                'default_subject' => 'Gracias por elegirnos.',
                'default_body' => 'Hola {customer_name},

Gracias por elegirnos. Tu reserva ya está completada.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Reserva completada — administrador',
                'description' => 'Se añade a la cola para el administrador cuando una reserva se marca como completada.',
                'default_subject' => 'Reserva completada: #{booking_id}',
                'default_body' => 'Se ha completado una reserva.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Número de reserva: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Servicio modificado — cliente',
                'description' => 'Se añade a la cola para el cliente cuando se cambia el servicio o paquete de la reserva.',
                'default_subject' => 'El servicio de tu reserva ha cambiado',
                'default_body' => 'Hola {customer_name},

El servicio de tu reserva ha cambiado.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Servicio modificado — administrador',
                'description' => 'Se añade a la cola para el administrador cuando se cambia el servicio o paquete de la reserva.',
                'default_subject' => 'Servicio de la reserva modificado: #{booking_id}',
                'default_body' => 'Se ha cambiado el servicio de una reserva.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Número de reserva: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Pago pendiente — cliente',
                'description' => 'Se añade a la cola para el cliente cuando el pago está pendiente o requiere una acción.',
                'default_subject' => 'El pago de tu reserva está pendiente',
                'default_body' => 'Hola {customer_name},

El pago de tu reserva está pendiente.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Pago: {payment_status_label}

Resumen del precio:
{price_summary}

Número de reserva: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Pago pendiente — administrador',
                'description' => 'Se añade a la cola para el administrador cuando el pago está pendiente o requiere una acción.',
                'default_subject' => 'Pago pendiente para la reserva #{booking_id}',
                'default_body' => 'El pago está pendiente.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Pago: {payment_status_label}

Resumen del precio:
{price_summary}

Número de reserva: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Confirmación de pago — cliente',
                'description' => 'Se añade a la cola para el cliente cuando se confirma el pago.',
                'default_subject' => 'Pago recibido',
                'default_body' => 'Hola {customer_name},

Hemos recibido tu pago.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Pago: {payment_status_label}

Resumen del precio:
{price_summary}

Número de reserva: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Confirmación de pago — administrador',
                'description' => 'Se añade a la cola para el administrador cuando se confirma el pago.',
                'default_subject' => 'Pago recibido para la reserva #{booking_id}',
                'default_body' => 'Pago recibido.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Pago: {payment_status_label}

Resumen del precio:
{price_summary}

Número de reserva: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Pago fallido — cliente',
                'description' => 'Se añade a la cola para el cliente cuando falla el pago.',
                'default_subject' => 'El pago ha fallado',
                'default_body' => 'Hola {customer_name},

No se pudo completar tu pago.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Pago fallido — administrador',
                'description' => 'Se añade a la cola para el administrador cuando falla el pago.',
                'default_subject' => 'Pago fallido para la reserva #{booking_id}',
                'default_body' => 'El pago ha fallado.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Pago: {payment_status_label}
Número de reserva: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Pago reembolsado — cliente',
                'description' => 'Se añade a la cola para el cliente cuando se reembolsa el pago.',
                'default_subject' => 'Tu pago ha sido reembolsado',
                'default_body' => 'Hola {customer_name},

Tu pago ha sido reembolsado.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Número de reserva: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Pago reembolsado — administrador',
                'description' => 'Se añade a la cola para el administrador cuando se reembolsa el pago.',
                'default_subject' => 'Pago reembolsado para la reserva #{booking_id}',
                'default_body' => 'Pago reembolsado.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Número de reserva: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Factura creada — cliente',
                'description' => 'Se añade a la cola para el cliente cuando se crea una factura.',
                'default_subject' => 'Factura de la reserva #{booking_id}',
                'default_body' => 'Hola {customer_name},

Se ha creado una factura para tu reserva.

Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}

Resumen del precio:
{price_summary}

Número de reserva: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Factura creada — administrador',
                'description' => 'Se añade a la cola para el administrador cuando se crea una factura.',
                'default_subject' => 'Factura creada para la reserva #{booking_id}',
                'default_body' => 'Se ha creado una factura.

Cliente: {customer_name}
Correo electrónico: {customer_email}
Servicio: {package_title}
Fecha: {booking_date}
Hora: {start_time} - {end_time}
Número de reserva: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Enlace de acceso — cliente',
                'description' => 'Plantilla para futuros correos de acceso de clientes.',
                'default_subject' => 'Tu enlace de acceso',
                'default_body' => 'Hola {customer_name},

Usa este enlace para acceder a tu cuenta:

{magic_link}

Este enlace caduca pronto.',
            ],
            'contact_form_admin' => [
                'title' => 'Formulario de contacto — administrador',
                'description' => 'Se envía al administrador cuando un visitante remite el formulario de contacto de Slotera.',
                'default_subject' => '[{site_name}] Nuevo mensaje de contacto',
                'default_body' => 'Nuevo mensaje del formulario de contacto.

Nombre: {contact_name}
Correo electrónico: {contact_email}
Teléfono: {contact_phone}
Asunto: {contact_subject}
Mensaje:
{contact_message}

Página: {contact_page_title}
URL: {contact_page_url}
Enviado: {contact_submitted_at}
Idioma: {contact_locale}
IP: {contact_user_ip}
Agente de usuario: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Marketing — promoción',
                'description' => 'Plantilla de marketing reutilizable para campañas promocionales, ofertas y correos de recuperación de clientes.',
                'default_subject' => '{headline}',
                'default_body' => 'Hola {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Oferta especial</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Tu código promocional</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · válido hasta {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'fi' => [
            'booking_created_customer' => [
                'title' => 'Varaus luotu — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun varaus luodaan.',
                'default_subject' => 'Varauspyyntösi on vastaanotettu',
                'default_body' => 'Hei {customer_name},

Kiitos varauksestasi. Olemme vastaanottaneet pyyntösi.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Varauksen tila: {status_label}
Maksu: {payment_status_label}

Hintayhteenveto:
{price_summary}

Varausnumero: #{booking_id}

Peruuta varaus: {cancellation_url}
Aikatauluta varaus uudelleen: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Uusi varaus — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun uusi varaus luodaan.',
                'default_subject' => 'Uusi varaus vastaanotettu',
                'default_body' => 'Uusi varaus on vastaanotettu.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Puhelin: {customer_phone}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Varauksen tila: {status_label}
Maksu: {payment_status_label}

Hintayhteenveto:
{price_summary}

Varausnumero: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Varaus vahvistettu — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun varaus vahvistetaan.',
                'default_subject' => 'Varauksesi on vahvistettu',
                'default_body' => 'Hei {customer_name},

Varauksesi on vahvistettu.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}

Peruuta varaus: {cancellation_url}
Aikatauluta varaus uudelleen: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Varaus vahvistettu — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun varaus vahvistetaan.',
                'default_subject' => 'Varaus vahvistettu: #{booking_id}',
                'default_body' => 'Varaus on vahvistettu.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Varausnumero: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Muistutus 24 h — asiakas',
                'description' => 'Lisätään automaattisesti lähetysjonoon 24 tuntia ennen vahvistettua varausta.',
                'default_subject' => 'Muistutus: varauksesi on huomenna',
                'default_body' => 'Hei {customer_name},

Tämä on muistutus tulevasta varauksestasi.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Peruuta varaus: {cancellation_url}
Aikatauluta varaus uudelleen: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Muistutus 2 h — asiakas',
                'description' => 'Lisätään automaattisesti lähetysjonoon 2 tuntia ennen vahvistettua varausta.',
                'default_subject' => 'Muistutus: varauksesi alkaa pian',
                'default_body' => 'Hei {customer_name},

Varauksesi alkaa pian.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Varaus peruutettu — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun varaus peruutetaan.',
                'default_subject' => 'Varauksesi on peruutettu',
                'default_body' => 'Hei {customer_name},

Varauksesi on peruutettu.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Varaus peruutettu — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun varaus peruutetaan.',
                'default_subject' => 'Varaus peruutettu: #{booking_id}',
                'default_body' => 'Varaus on peruutettu.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Varaus siirretty — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun varauksen ajankohtaa muutetaan.',
                'default_subject' => 'Varauksesi ajankohtaa on muutettu',
                'default_body' => 'Hei {customer_name},

Varauksesi ajankohtaa on muutettu.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}

Peruuta varaus: {cancellation_url}
Aikatauluta varaus uudelleen: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Varaus siirretty — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun varauksen ajankohtaa muutetaan.',
                'default_subject' => 'Varauksen ajankohta muutettu: #{booking_id}',
                'default_body' => 'Varauksen ajankohtaa on muutettu.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Varauksen tila: {status_label}
Maksu: {payment_status_label}
Varausnumero: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Varaus valmis — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun varaus merkitään valmiiksi.',
                'default_subject' => 'Kiitos, että valitsit meidät.',
                'default_body' => 'Hei {customer_name},

Kiitos, että valitsit meidät. Varauksesi on nyt valmis.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Varaus valmis — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun varaus merkitään valmiiksi.',
                'default_subject' => 'Varaus valmis: #{booking_id}',
                'default_body' => 'Varaus on merkitty valmiiksi.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Varausnumero: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Palvelu muutettu — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun varauksen palvelua tai pakettia muutetaan.',
                'default_subject' => 'Varauksesi palvelua on muutettu',
                'default_body' => 'Hei {customer_name},

Varauksesi palvelua on muutettu.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Palvelu muutettu — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun varauksen palvelua tai pakettia muutetaan.',
                'default_subject' => 'Varauksen palvelu muutettu: #{booking_id}',
                'default_body' => 'Varauksen palvelua on muutettu.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Varausnumero: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Maksu odottaa — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun maksu odottaa tai vaatii toimenpiteitä.',
                'default_subject' => 'Varauksesi maksu odottaa',
                'default_body' => 'Hei {customer_name},

Varauksesi maksu odottaa.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Maksu: {payment_status_label}

Hintayhteenveto:
{price_summary}

Varausnumero: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Maksu odottaa — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun maksu odottaa tai vaatii toimenpiteitä.',
                'default_subject' => 'Varausta #{booking_id} koskeva maksu odottaa',
                'default_body' => 'Maksu odottaa.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Maksu: {payment_status_label}

Hintayhteenveto:
{price_summary}

Varausnumero: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Maksu vastaanotettu — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun maksu vahvistetaan.',
                'default_subject' => 'Maksusi on vastaanotettu',
                'default_body' => 'Hei {customer_name},

Olemme vastaanottaneet maksusi.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Maksu: {payment_status_label}

Hintayhteenveto:
{price_summary}

Varausnumero: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Maksu vastaanotettu — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun maksu vahvistetaan.',
                'default_subject' => 'Varausta #{booking_id} koskeva maksu vastaanotettu',
                'default_body' => 'Maksu vastaanotettu.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Maksu: {payment_status_label}

Hintayhteenveto:
{price_summary}

Varausnumero: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Maksu epäonnistui — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun maksu epäonnistuu.',
                'default_subject' => 'Maksu epäonnistui',
                'default_body' => 'Hei {customer_name},

Maksuasi ei voitu suorittaa loppuun.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Maksu epäonnistui — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun maksu epäonnistuu.',
                'default_subject' => 'Varauksen #{booking_id} maksu epäonnistui',
                'default_body' => 'Maksu epäonnistui.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Maksu: {payment_status_label}
Varausnumero: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Maksu palautettu — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun maksu palautetaan.',
                'default_subject' => 'Maksusi on palautettu',
                'default_body' => 'Hei {customer_name},

Maksusi on palautettu.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Varausnumero: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Maksu palautettu — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun maksu palautetaan.',
                'default_subject' => 'Varauksen #{booking_id} maksu palautettu',
                'default_body' => 'Maksu palautettu.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Varausnumero: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Lasku luotu — asiakas',
                'description' => 'Lisätään asiakkaan lähetysjonoon, kun lasku luodaan.',
                'default_subject' => 'Varauksen #{booking_id} lasku',
                'default_body' => 'Hei {customer_name},

Varauksestasi on luotu lasku.

Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}

Hintayhteenveto:
{price_summary}

Varausnumero: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Lasku luotu — ylläpitäjä',
                'description' => 'Lisätään ylläpitäjän lähetysjonoon, kun lasku luodaan.',
                'default_subject' => 'Lasku luotu varaukselle #{booking_id}',
                'default_body' => 'Lasku on luotu.

Asiakas: {customer_name}
Sähköposti: {customer_email}
Palvelu: {package_title}
Päivämäärä: {booking_date}
Aika: {start_time} - {end_time}
Varausnumero: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Kirjautumislinkki — asiakas',
                'description' => 'Mallipohja asiakkaan tulevia kirjautumisviestejä varten.',
                'default_subject' => 'Kirjautumislinkkisi',
                'default_body' => 'Hei {customer_name},

Kirjaudu tilillesi tämän linkin kautta:

{magic_link}

Linkki vanhenee pian.',
            ],
            'contact_form_admin' => [
                'title' => 'Yhteydenottolomake — ylläpitäjä',
                'description' => 'Lähetetään ylläpitäjälle, kun kävijä lähettää Sloteran yhteydenottolomakkeen.',
                'default_subject' => '[{site_name}] Uusi yhteydenottoviesti',
                'default_body' => 'Uusi viesti yhteydenottolomakkeelta.

Nimi: {contact_name}
Sähköposti: {contact_email}
Puhelin: {contact_phone}
Aihe: {contact_subject}
Viesti:
{contact_message}

Sivu: {contact_page_title}
URL: {contact_page_url}
Lähetetty: {contact_submitted_at}
Kielialue: {contact_locale}
IP: {contact_user_ip}
Selainagentti: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Markkinointi — kampanja',
                'description' => 'Uudelleenkäytettävä markkinointimalli kampanjoita, tarjouksia ja paluuviestejä varten.',
                'default_subject' => '{headline}',
                'default_body' => 'Hei {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Erikoistarjous</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Tarjouskoodisi</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · voimassa asti {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'sv_SE' => [
            'booking_created_customer' => [
                'title' => 'Bokning skapad — kund',
                'description' => 'Köas till kunden när en bokning skapas.',
                'default_subject' => 'Vi har tagit emot din bokningsförfrågan',
                'default_body' => 'Hej {customer_name},

Tack för din bokning. Vi har tagit emot din förfrågan.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Bokningsstatus: {status_label}
Betalning: {payment_status_label}

Prissammanfattning:
{price_summary}

Bokningsnummer: #{booking_id}

Avboka bokning: {cancellation_url}
Boka om: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Ny bokning — administratör',
                'description' => 'Köas till administratören när en ny bokning skapas.',
                'default_subject' => 'Ny bokning mottagen',
                'default_body' => 'En ny bokning har tagits emot.

Kund: {customer_name}
E-post: {customer_email}
Telefon: {customer_phone}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Bokningsstatus: {status_label}
Betalning: {payment_status_label}

Prissammanfattning:
{price_summary}

Bokningsnummer: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Bokning bekräftad — kund',
                'description' => 'Köas till kunden när en bokning bekräftas.',
                'default_subject' => 'Din bokning är bekräftad',
                'default_body' => 'Hej {customer_name},

Din bokning är bekräftad.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}

Avboka bokning: {cancellation_url}
Boka om: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Bokning bekräftad — administratör',
                'description' => 'Köas till administratören när en bokning bekräftas.',
                'default_subject' => 'Bokning bekräftad: #{booking_id}',
                'default_body' => 'En bokning har bekräftats.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Bokningsnummer: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Påminnelse 24 tim — kund',
                'description' => 'Köas automatiskt 24 timmar före en bekräftad bokning.',
                'default_subject' => 'Påminnelse: din bokning är i morgon',
                'default_body' => 'Hej {customer_name},

Detta är en påminnelse om din kommande bokning.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Avboka bokning: {cancellation_url}
Boka om: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Påminnelse 2 tim — kund',
                'description' => 'Köas automatiskt 2 timmar före en bekräftad bokning.',
                'default_subject' => 'Påminnelse: din bokning börjar snart',
                'default_body' => 'Hej {customer_name},

Din bokning börjar snart.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Bokning avbokad — kund',
                'description' => 'Köas till kunden när en bokning avbokas.',
                'default_subject' => 'Din bokning har avbokats',
                'default_body' => 'Hej {customer_name},

Din bokning har avbokats.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Bokning avbokad — administratör',
                'description' => 'Köas till administratören när en bokning avbokas.',
                'default_subject' => 'Bokning avbokad: #{booking_id}',
                'default_body' => 'En bokning har avbokats.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Bokning ombokad — kund',
                'description' => 'Köas till kunden när en bokning bokas om.',
                'default_subject' => 'Din bokning har bokats om',
                'default_body' => 'Hej {customer_name},

Din bokning har bokats om.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}

Avboka bokning: {cancellation_url}
Boka om: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Bokning ombokad — administratör',
                'description' => 'Köas till administratören när en bokning bokas om.',
                'default_subject' => 'Bokning ombokad: #{booking_id}',
                'default_body' => 'En bokning har bokats om.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Bokningsstatus: {status_label}
Betalning: {payment_status_label}
Bokningsnummer: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Bokning slutförd — kund',
                'description' => 'Köas till kunden när en bokning markeras som slutförd.',
                'default_subject' => 'Tack för att du valde oss.',
                'default_body' => 'Hej {customer_name},

Tack för att du valde oss. Din bokning är nu slutförd.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Bokning slutförd — administratör',
                'description' => 'Köas till administratören när en bokning markeras som slutförd.',
                'default_subject' => 'Bokning slutförd: #{booking_id}',
                'default_body' => 'En bokning har slutförts.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Bokningsnummer: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Tjänst ändrad — kund',
                'description' => 'Köas till kunden när bokningens tjänst eller paket ändras.',
                'default_subject' => 'Tjänsten för din bokning har ändrats',
                'default_body' => 'Hej {customer_name},

Tjänsten för din bokning har ändrats.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Tjänst ändrad — administratör',
                'description' => 'Köas till administratören när bokningens tjänst eller paket ändras.',
                'default_subject' => 'Bokningens tjänst ändrad: #{booking_id}',
                'default_body' => 'Tjänsten för en bokning har ändrats.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Bokningsnummer: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Betalning väntar — kund',
                'description' => 'Köas till kunden när betalningen väntar eller kräver en åtgärd.',
                'default_subject' => 'Betalningen för din bokning väntar',
                'default_body' => 'Hej {customer_name},

Betalningen för din bokning väntar.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Betalning: {payment_status_label}

Prissammanfattning:
{price_summary}

Bokningsnummer: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Betalning väntar — administratör',
                'description' => 'Köas till administratören när betalningen väntar eller kräver en åtgärd.',
                'default_subject' => 'Betalning väntar för bokning #{booking_id}',
                'default_body' => 'Payment is pending.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Betalning: {payment_status_label}

Prissammanfattning:
{price_summary}

Bokningsnummer: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Betalning mottagen — kund',
                'description' => 'Köas till kunden när en betalning bekräftas.',
                'default_subject' => 'Din betalning har tagits emot',
                'default_body' => 'Hej {customer_name},

Vi har tagit emot din betalning.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Betalning: {payment_status_label}

Prissammanfattning:
{price_summary}

Bokningsnummer: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Betalning mottagen — administratör',
                'description' => 'Köas till administratören när en betalning bekräftas.',
                'default_subject' => 'Betalning mottagen för bokning #{booking_id}',
                'default_body' => 'Betalning mottagen.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Betalning: {payment_status_label}

Prissammanfattning:
{price_summary}

Bokningsnummer: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Betalning misslyckades — kund',
                'description' => 'Köas till kunden när en betalning misslyckas.',
                'default_subject' => 'Din betalning misslyckades',
                'default_body' => 'Hej {customer_name},

Din betalning kunde inte slutföras.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Betalning misslyckades — administratör',
                'description' => 'Köas till administratören när en betalning misslyckas.',
                'default_subject' => 'Betalning misslyckades för bokning #{booking_id}',
                'default_body' => 'Betalningen misslyckades.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Betalning: {payment_status_label}
Bokningsnummer: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Betalning återbetald — kund',
                'description' => 'Köas till kunden när en betalning återbetalas.',
                'default_subject' => 'Din betalning har återbetalats',
                'default_body' => 'Hej {customer_name},

Din betalning har återbetalats.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Bokningsnummer: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Betalning återbetald — administratör',
                'description' => 'Köas till administratören när en betalning återbetalas.',
                'default_subject' => 'Betalning återbetald för bokning #{booking_id}',
                'default_body' => 'Betalningen har återbetalats.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Bokningsnummer: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Faktura skapad — kund',
                'description' => 'Köas till kunden när en faktura skapas.',
                'default_subject' => 'Faktura för bokning #{booking_id}',
                'default_body' => 'Hej {customer_name},

En faktura har skapats för din bokning.

Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}

Prissammanfattning:
{price_summary}

Bokningsnummer: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Faktura skapad — administratör',
                'description' => 'Köas till administratören när en faktura skapas.',
                'default_subject' => 'Faktura skapad för bokning #{booking_id}',
                'default_body' => 'En faktura har skapats.

Kund: {customer_name}
E-post: {customer_email}
Tjänst: {package_title}
Datum: {booking_date}
Tid: {start_time} - {end_time}
Bokningsnummer: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Inloggningslänk — kund',
                'description' => 'Mall för framtida e-postmeddelanden för kundinloggning.',
                'default_subject' => 'Din inloggningslänk',
                'default_body' => 'Hej {customer_name},

Använd den här länken för att logga in på ditt konto:

{magic_link}

Länken upphör snart att gälla.',
            ],
            'contact_form_admin' => [
                'title' => 'Kontaktformulär — administratör',
                'description' => 'Skickas till administratören när en besökare skickar Slotera-kontaktformuläret.',
                'default_subject' => '[{site_name}] Nytt kontaktmeddelande',
                'default_body' => 'Nytt meddelande från kontaktformuläret.

Namn: {contact_name}
E-post: {contact_email}
Telefon: {contact_phone}
Ämne: {contact_subject}
Meddelande:
{contact_message}

Sida: {contact_page_title}
URL: {contact_page_url}
Skickat: {contact_submitted_at}
Språkversion: {contact_locale}
IP: {contact_user_ip}
Webbläsaragent: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Marknadsföring — kampanj',
                'description' => 'Återanvändbar marknadsföringsmall för kampanjer, erbjudanden och återaktiveringsmeddelanden.',
                'default_subject' => '{headline}',
                'default_body' => 'Hej {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Specialerbjudande</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Din rabattkod</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · giltig till {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'fr_FR' => [
            'booking_created_customer' => [
                'title' => 'Réservation créée — client',
                'description' => 'Mis en file d’attente pour le client lors de la création d’une réservation.',
                'default_subject' => 'Votre demande de réservation a bien été reçue',
                'default_body' => "Bonjour {customer_name},\n\nMerci pour votre réservation. Nous avons bien reçu votre demande.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nStatut : {status_label}\nPaiement : {payment_status_label}\n\nRécapitulatif du prix :\n{price_summary}\n\nNuméro de réservation : #{booking_id}\n\nAnnuler la réservation : {cancellation_url}\nModifier la réservation : {reschedule_url}",
            ],
            'booking_created_admin' => [
                'title' => 'Nouvelle réservation — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lors de la création d’une réservation.',
                'default_subject' => 'Nouvelle réservation reçue',
                'default_body' => "Une nouvelle réservation a été reçue.\n\nClient : {customer_name}\nE-mail : {customer_email}\nTéléphone : {customer_phone}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nStatut : {status_label}\nPaiement : {payment_status_label}\n\nRécapitulatif du prix :\n{price_summary}\n\nNuméro de réservation : #{booking_id}",
            ],
            'booking_confirmed_customer' => [
                'title' => 'Réservation confirmée — client',
                'description' => 'Mis en file d’attente pour le client lorsque la réservation est confirmée.',
                'default_subject' => 'Votre réservation est confirmée',
                'default_body' => "Bonjour {customer_name},\n\nVotre réservation est confirmée.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nNuméro de réservation : #{booking_id}\n\nAnnuler la réservation : {cancellation_url}\nModifier la réservation : {reschedule_url}",
            ],
            'booking_confirmed_admin' => [
                'title' => 'Réservation confirmée — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque la réservation est confirmée.',
                'default_subject' => 'Réservation confirmée : #{booking_id}',
                'default_body' => "Une réservation a été confirmée.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nNuméro de réservation : #{booking_id}",
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Rappel 24 h — client',
                'description' => 'Mis automatiquement en file d’attente 24 heures avant une réservation confirmée.',
                'default_subject' => 'Rappel : votre réservation est prévue demain',
                'default_body' => "Bonjour {customer_name},\n\nVoici un rappel concernant votre prochaine réservation.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nAnnuler la réservation : {cancellation_url}\nModifier la réservation : {reschedule_url}",
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Rappel 2 h — client',
                'description' => 'Mis automatiquement en file d’attente 2 heures avant une réservation confirmée.',
                'default_subject' => 'Rappel : votre réservation commence bientôt',
                'default_body' => "Bonjour {customer_name},\n\nVotre réservation commence bientôt.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}",
            ],
            'booking_cancelled_customer' => [
                'title' => 'Réservation annulée — client',
                'description' => 'Mis en file d’attente pour le client lorsque la réservation est annulée.',
                'default_subject' => 'Votre réservation a été annulée',
                'default_body' => "Bonjour {customer_name},\n\nVotre réservation a été annulée.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nNuméro de réservation : #{booking_id}",
            ],
            'booking_cancelled_admin' => [
                'title' => 'Réservation annulée — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque la réservation est annulée.',
                'default_subject' => 'Réservation annulée : #{booking_id}',
                'default_body' => "Une réservation a été annulée.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nNuméro de réservation : #{booking_id}",
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Réservation reprogrammée — client',
                'description' => 'Mis en file d’attente pour le client lorsque la réservation est reprogrammée.',
                'default_subject' => 'Votre réservation a été reprogrammée',
                'default_body' => "Bonjour {customer_name},\n\nVotre réservation a été reprogrammée.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nNuméro de réservation : #{booking_id}\n\nAnnuler la réservation : {cancellation_url}\nModifier la réservation : {reschedule_url}",
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Réservation reprogrammée — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque la réservation est reprogrammée.',
                'default_subject' => 'Réservation reprogrammée : #{booking_id}',
                'default_body' => "Une réservation a été reprogrammée.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nStatut : {status_label}\nPaiement : {payment_status_label}\nNuméro de réservation : #{booking_id}",
            ],
            'booking_completed_customer' => [
                'title' => 'Réservation terminée — client',
                'description' => 'Mis en file d’attente pour le client lorsque la réservation est marquée comme terminée.',
                'default_subject' => 'Merci de nous avoir choisis.',
                'default_body' => "Bonjour {customer_name},\n\nMerci de nous avoir choisis. Votre réservation est maintenant terminée.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nNuméro de réservation : #{booking_id}",
            ],
            'booking_completed_admin' => [
                'title' => 'Réservation terminée — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque la réservation est marquée comme terminée.',
                'default_subject' => 'Réservation terminée : #{booking_id}',
                'default_body' => "Une réservation a été terminée.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nNuméro de réservation : #{booking_id}",
            ],
            'package_changed_customer' => [
                'title' => 'Service modifié — client',
                'description' => 'Mis en file d’attente pour le client lorsque le service ou l’offre de la réservation est modifié.',
                'default_subject' => 'Le service de votre réservation a été modifié',
                'default_body' => "Bonjour {customer_name},\n\nLe service associé à votre réservation a été modifié.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nNuméro de réservation : #{booking_id}",
            ],
            'package_changed_admin' => [
                'title' => 'Service modifié — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque le service ou l’offre de la réservation est modifié.',
                'default_subject' => 'Service de réservation modifié : #{booking_id}',
                'default_body' => "Le service associé à une réservation a été modifié.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nNuméro de réservation : #{booking_id}",
            ],
            'payment_pending_customer' => [
                'title' => 'Paiement en attente — client',
                'description' => 'Mis en file d’attente pour le client lorsque le paiement est en attente ou nécessite une action.',
                'default_subject' => 'Le paiement de votre réservation est en attente',
                'default_body' => "Bonjour {customer_name},\n\nLe paiement de votre réservation est en attente.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nPaiement : {payment_status_label}\n\nRécapitulatif du prix :\n{price_summary}\n\nNuméro de réservation : #{booking_id}",
            ],
            'payment_pending_admin' => [
                'title' => 'Paiement en attente — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque le paiement est en attente ou nécessite une action.',
                'default_subject' => 'Paiement en attente pour la réservation #{booking_id}',
                'default_body' => "Un paiement est en attente.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nPaiement : {payment_status_label}\n\nRécapitulatif du prix :\n{price_summary}\n\nNuméro de réservation : #{booking_id}",
            ],
            'payment_received_customer' => [
                'title' => 'Confirmation de paiement — client',
                'description' => 'Mis en file d’attente pour le client lorsque le paiement est confirmé.',
                'default_subject' => 'Paiement reçu',
                'default_body' => "Bonjour {customer_name},\n\nNous avons bien reçu votre paiement.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nPaiement : {payment_status_label}\n\nRécapitulatif du prix :\n{price_summary}\n\nNuméro de réservation : #{booking_id}",
            ],
            'payment_received_admin' => [
                'title' => 'Confirmation de paiement — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque le paiement est confirmé.',
                'default_subject' => 'Paiement reçu pour la réservation #{booking_id}',
                'default_body' => "Paiement reçu.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nPaiement : {payment_status_label}\n\nRécapitulatif du prix :\n{price_summary}\n\nNuméro de réservation : #{booking_id}",
            ],
            'payment_failed_customer' => [
                'title' => 'Échec du paiement — client',
                'description' => 'Mis en file d’attente pour le client lorsque le paiement échoue.',
                'default_subject' => 'Le paiement a échoué',
                'default_body' => "Bonjour {customer_name},\n\nVotre paiement n’a pas pu être effectué.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nNuméro de réservation : #{booking_id}",
            ],
            'payment_failed_admin' => [
                'title' => 'Échec du paiement — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque le paiement échoue.',
                'default_subject' => 'Échec du paiement pour la réservation #{booking_id}',
                'default_body' => "Le paiement a échoué.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nPaiement : {payment_status_label}\nNuméro de réservation : #{booking_id}",
            ],
            'payment_refunded_customer' => [
                'title' => 'Paiement remboursé — client',
                'description' => 'Mis en file d’attente pour le client lorsque le paiement est remboursé.',
                'default_subject' => 'Votre paiement a été remboursé',
                'default_body' => "Bonjour {customer_name},\n\nVotre paiement a été remboursé.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nNuméro de réservation : #{booking_id}",
            ],
            'payment_refunded_admin' => [
                'title' => 'Paiement remboursé — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsque le paiement est remboursé.',
                'default_subject' => 'Paiement remboursé pour la réservation #{booking_id}',
                'default_body' => "Paiement remboursé.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nNuméro de réservation : #{booking_id}",
            ],
            'invoice_created_customer' => [
                'title' => 'Facture créée — client',
                'description' => 'Mis en file d’attente pour le client lorsqu’une facture est créée.',
                'default_subject' => 'Facture de la réservation #{booking_id}',
                'default_body' => "Bonjour {customer_name},\n\nUne facture a été créée pour votre réservation.\n\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\n\nRécapitulatif du prix :\n{price_summary}\n\nNuméro de réservation : #{booking_id}",
            ],
            'invoice_created_admin' => [
                'title' => 'Facture créée — administrateur',
                'description' => 'Mis en file d’attente pour l’administrateur lorsqu’une facture est créée.',
                'default_subject' => 'Facture créée pour la réservation #{booking_id}',
                'default_body' => "Une facture a été créée.\n\nClient : {customer_name}\nE-mail : {customer_email}\nService : {package_title}\nDate : {booking_date}\nHeure : {start_time} - {end_time}\nNuméro de réservation : #{booking_id}",
            ],
            'magic_link_customer' => [
                'title' => 'Lien de connexion — client',
                'description' => 'Modèle destiné aux futurs e-mails de connexion à l’espace client.',
                'default_subject' => 'Votre lien de connexion',
                'default_body' => "Bonjour {customer_name},\n\nUtilisez ce lien pour vous connecter à votre compte :\n\n{magic_link}\n\nCe lien expirera prochainement.",
            ],
            'contact_form_admin' => [
                'title' => 'Formulaire de contact — administrateur',
                'description' => 'Envoyé à l’administrateur lorsqu’un visiteur soumet le formulaire de contact Slotera.',
                'default_subject' => 'Nouveau message du formulaire de contact — {site_name}',
                'default_body' => "Nouveau message du formulaire de contact.\n\nNom : {contact_name}\nE-mail : {contact_email}\nTéléphone : {contact_phone}\nObjet : {contact_subject}\nMessage :\n{contact_message}\n\nPage : {contact_page_title}\nURL : {contact_page_url}\nEnvoyé le : {contact_submitted_at}\nLangue : {contact_locale}\nIP : {contact_user_ip}\nNavigateur / appareil : {contact_user_agent}",
            ],
            'marketing_promo' => [
                'title' => 'Campagne promotionnelle',
                'description' => 'Modèle utilisé pour les campagnes promotionnelles envoyées à une audience sélectionnée.',
                'default_subject' => '{headline}',
                'default_body' => "Bonjour {customer_name},\n\n{headline}\n\n{message}\n\n{submessage}\n\n{coupon_code}\n\n{cta_url}",
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Offre spéciale</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Votre code promotionnel</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · valable jusqu’au {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'bg_BG' => [
            'booking_created_customer' => [
                'title' => 'Създадена резервация — клиент',
                'description' => 'Поставя се в опашката за клиента при създаване на резервация.',
                'default_subject' => 'Получена е нова резервация',
                'default_body' => 'Здравейте, {customer_name},\n\nБлагодарим ви за резервацията. Вашата резервация беше получена успешно.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nСтатус: {status_label}\nПлащане: {payment_status_label}\n\nОбобщение на цената:\n{price_summary}\n\nНомер на резервация: #{booking_id}\n\nОтказ от резервацията: {cancellation_url}\nПренасрочване на резервацията: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Нова резервация — администратор',
                'description' => 'Поставя се в опашката за администратора при създаване на нова резервация.',
                'default_subject' => 'Получена е нова резервация',
                'default_body' => 'Получена е нова резервация.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nТелефон: {customer_phone}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nСтатус: {status_label}\nПлащане: {payment_status_label}\n\nОбобщение на цената:\n{price_summary}\n\nНомер на резервация: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Потвърдена резервация — клиент',
                'description' => 'Поставя се в опашката за клиента при потвърждаване на резервация.',
                'default_subject' => 'Вашата резервация е потвърдена',
                'default_body' => 'Здравейте, {customer_name},\n\nВашата резервация е потвърдена.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nНомер на резервация: #{booking_id}\n\nОтказ от резервацията: {cancellation_url}\nПренасрочване на резервацията: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Потвърдена резервация — администратор',
                'description' => 'Поставя се в опашката за администратора при потвърждаване на резервация.',
                'default_subject' => 'Потвърдена резервация: #{booking_id}',
                'default_body' => 'Резервацията е потвърдена.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nНомер на резервация: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Напомняне 24 ч. — клиент',
                'description' => 'Поставя се автоматично в опашката 24 часа преди потвърдена резервация.',
                'default_subject' => 'Напомняне: резервацията ви е утре',
                'default_body' => 'Здравейте, {customer_name},\n\nНапомняме ви за предстоящата резервация.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nОтказ от резервацията: {cancellation_url}\nПренасрочване на резервацията: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Напомняне 2 ч. — клиент',
                'description' => 'Поставя се автоматично в опашката 2 часа преди потвърдена резервация.',
                'default_subject' => 'Напомняне: резервацията ви започва скоро',
                'default_body' => 'Здравейте, {customer_name},\n\nВашата резервация започва скоро.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Отменена резервация — клиент',
                'description' => 'Поставя се в опашката за клиента при отмяна на резервация.',
                'default_subject' => 'Вашата резервация е отменена',
                'default_body' => 'Здравейте, {customer_name},\n\nВашата резервация е отменена.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nНомер на резервация: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Отменена резервация — администратор',
                'description' => 'Поставя се в опашката за администратора при отмяна на резервация.',
                'default_subject' => 'Отменена резервация: #{booking_id}',
                'default_body' => 'Резервацията е отменена.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nНомер на резервация: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Променена резервация — клиент',
                'description' => 'Поставя се в опашката за клиента при промяна на резервация.',
                'default_subject' => 'Вашата резервация е променена',
                'default_body' => 'Здравейте, {customer_name},\n\nВашата резервация е променена.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nНомер на резервация: #{booking_id}\n\nОтказ от резервацията: {cancellation_url}\nПренасрочване на резервацията: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Променена резервация — администратор',
                'description' => 'Поставя се в опашката за администратора при промяна на резервация.',
                'default_subject' => 'Променена резервация: #{booking_id}',
                'default_body' => 'Резервацията е променена.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nСтатус: {status_label}\nПлащане: {payment_status_label}\nНомер на резервация: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Завършена резервация — клиент',
                'description' => 'Поставя се в опашката за клиента, когато резервацията бъде отбелязана като завършена.',
                'default_subject' => 'Благодарим ви, че ни избрахте.',
                'default_body' => 'Здравейте, {customer_name},\n\nБлагодарим ви, че ни избрахте. Вашата резервация вече е завършена.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nНомер на резервация: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Завършена резервация — администратор',
                'description' => 'Поставя се в опашката за администратора, когато резервацията бъде отбелязана като завършена.',
                'default_subject' => 'Завършена резервация: #{booking_id}',
                'default_body' => 'Резервацията е завършена.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nНомер на резервация: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Променена услуга — клиент',
                'description' => 'Поставя се в опашката за клиента при промяна на услугата или пакета на резервацията.',
                'default_subject' => 'Услугата по вашата резервация е променена',
                'default_body' => 'Здравейте, {customer_name},\n\nУслугата по вашата резервация е променена.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nНомер на резервация: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Променена услуга — администратор',
                'description' => 'Поставя се в опашката за администратора при промяна на услугата или пакета на резервацията.',
                'default_subject' => 'Променена услуга за резервация: #{booking_id}',
                'default_body' => 'Услугата по резервацията е променена.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nНомер на резервация: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Очакващо плащане — клиент',
                'description' => 'Поставя се в опашката за клиента, когато плащането е в изчакване или изисква действие.',
                'default_subject' => 'Плащането за вашата резервация е в изчакване',
                'default_body' => 'Здравейте, {customer_name},\n\nПлащането за вашата резервация е в изчакване.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nПлащане: {payment_status_label}\n\nОбобщение на цената:\n{price_summary}\n\nНомер на резервация: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Очакващо плащане — администратор',
                'description' => 'Поставя се в опашката за администратора, когато плащането е в изчакване или изисква действие.',
                'default_subject' => 'Очакващо плащане за резервация #{booking_id}',
                'default_body' => 'Плащането е в изчакване.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nПлащане: {payment_status_label}\n\nОбобщение на цената:\n{price_summary}\n\nНомер на резервация: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Потвърждение на плащане — клиент',
                'description' => 'Поставя се в опашката за клиента при потвърждаване на плащането.',
                'default_subject' => 'Плащането е получено',
                'default_body' => 'Здравейте, {customer_name},\n\nПолучихме вашето плащане.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nПлащане: {payment_status_label}\n\nОбобщение на цената:\n{price_summary}\n\nНомер на резервация: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Потвърждение на плащане — администратор',
                'description' => 'Поставя се в опашката за администратора при потвърждаване на плащането.',
                'default_subject' => 'Получено плащане за резервация #{booking_id}',
                'default_body' => 'Получено е плащане.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nПлащане: {payment_status_label}\n\nОбобщение на цената:\n{price_summary}\n\nНомер на резервация: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Неуспешно плащане — клиент',
                'description' => 'Поставя се в опашката за клиента при неуспешно плащане.',
                'default_subject' => 'Плащането е неуспешно',
                'default_body' => 'Здравейте, {customer_name},\n\nВашето плащане не можа да бъде завършено.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nНомер на резервация: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Неуспешно плащане — администратор',
                'description' => 'Поставя се в опашката за администратора при неуспешно плащане.',
                'default_subject' => 'Неуспешно плащане за резервация #{booking_id}',
                'default_body' => 'Плащането е неуспешно.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nПлащане: {payment_status_label}\nНомер на резервация: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Възстановено плащане — клиент',
                'description' => 'Поставя се в опашката за клиента при възстановяване на плащането.',
                'default_subject' => 'Вашето плащане е възстановено',
                'default_body' => 'Здравейте, {customer_name},\n\nВашето плащане е възстановено.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nНомер на резервация: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Възстановено плащане — администратор',
                'description' => 'Поставя се в опашката за администратора при възстановяване на плащането.',
                'default_subject' => 'Възстановено плащане за резервация #{booking_id}',
                'default_body' => 'Плащането е възстановено.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nНомер на резервация: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Създадена фактура — клиент',
                'description' => 'Поставя се в опашката за клиента при създаване на фактура.',
                'default_subject' => 'Фактура за резервация #{booking_id}',
                'default_body' => 'Здравейте, {customer_name},\n\nЗа вашата резервация е създадена фактура.\n\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\n\nОбобщение на цената:\n{price_summary}\n\nНомер на резервация: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Създадена фактура — администратор',
                'description' => 'Поставя се в опашката за администратора при създаване на фактура.',
                'default_subject' => 'Създадена фактура за резервация #{booking_id}',
                'default_body' => 'Създадена е фактура.\n\nКлиент: {customer_name}\nИмейл: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nЧас: {start_time} - {end_time}\nНомер на резервация: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Връзка за вход — клиент',
                'description' => 'Шаблон за бъдещи имейли за вход в клиентския профил.',
                'default_subject' => 'Вашата връзка за вход',
                'default_body' => 'Здравейте, {customer_name},\n\nИзползвайте тази връзка, за да влезете в профила си:\n\n{magic_link}\n\nСрокът на тази връзка изтича скоро.',
            ],
            'contact_form_admin' => [
                'title' => 'Форма за контакт — администратор',
                'description' => 'Изпраща се до администратора, когато посетител изпрати формата за контакт на Slotera.',
                'default_subject' => '[{site_name}] Ново съобщение от формата за контакт',
                'default_body' => 'Ново съобщение от формата за контакт.\n\nИме: {contact_name}\nИмейл: {contact_email}\nТелефон: {contact_phone}\nТема: {contact_subject}\nСъобщение:\n{contact_message}\n\nСтраница: {contact_page_title}\nURL: {contact_page_url}\nИзпратено на: {contact_submitted_at}\nЕзикова настройка: {contact_locale}\nIP: {contact_user_ip}\nБраузър / устройство: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Маркетинг — промоция',
                'description' => 'Многократно използваем маркетингов шаблон за промоционални кампании, оферти и имейли за повторно ангажиране.',
                'default_subject' => '{headline}',
                'default_body' => 'Здравейте, {customer_name},\n\n{headline}\n\n{message}\n\n{submessage}\n\n{coupon_code}\n\n{cta_url}',
                'default_html_body' => '<div style="text-align:center;">\n  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Специална оферта</p>\n  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>\n  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>\n  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>\n  {cta_button}\n  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">\n    <p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Вашият код за отстъпка</p>\n    <p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>\n    <p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · валиден до {coupon_expires}</p>\n  </div>\n</div>',
            ],
        ],
        'lt_LT' => array (
  'booking_created_customer' => 
  array (
'title' => 'Užsakymas sukurtas — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai sukuriamas užsakymas.',
'default_subject' => 'Gautas naujas užsakymas',
'default_body' => 'Sveiki, {customer_name},

Dėkojame už užsakymą. Jūsų užsakymas sėkmingai gautas.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Būsena: {status_label}
Mokėjimas: {payment_status_label}

Kainos suvestinė:
{price_summary}

Užsakymo numeris: #{booking_id}

Atšaukti užsakymą: {cancellation_url}
Perkelti užsakymą: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Naujas užsakymas — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai sukuriamas naujas užsakymas.',
'default_subject' => 'Gautas naujas užsakymas',
'default_body' => 'Gautas naujas užsakymas.

Klientas: {customer_name}
El. paštas: {customer_email}
Telefonas: {customer_phone}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Būsena: {status_label}
Mokėjimas: {payment_status_label}

Kainos suvestinė:
{price_summary}

Užsakymo numeris: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Užsakymas patvirtintas — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai užsakymas patvirtinamas.',
'default_subject' => 'Jūsų užsakymas patvirtintas',
'default_body' => 'Sveiki, {customer_name},

Jūsų užsakymas patvirtintas.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}

Atšaukti užsakymą: {cancellation_url}
Perkelti užsakymą: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Užsakymas patvirtintas — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai užsakymas patvirtinamas.',
'default_subject' => 'Užsakymas patvirtintas: #{booking_id}',
'default_body' => 'Užsakymas patvirtintas.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Užsakymo numeris: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Priminimas prieš 24 val. — klientui',
'description' => 'Automatiškai įtraukiama į eilę likus 24 valandoms iki patvirtinto užsakymo.',
'default_subject' => 'Priminimas: jūsų užsakymas rytoj',
'default_body' => 'Sveiki, {customer_name},

Primename apie artėjantį užsakymą.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Atšaukti užsakymą: {cancellation_url}
Perkelti užsakymą: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Priminimas prieš 2 val. — klientui',
'description' => 'Automatiškai įtraukiama į eilę likus 2 valandoms iki patvirtinto užsakymo.',
'default_subject' => 'Priminimas: jūsų užsakymas netrukus prasidės',
'default_body' => 'Sveiki, {customer_name},

Jūsų užsakymas netrukus prasidės.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Užsakymas atšauktas — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai užsakymas atšaukiamas.',
'default_subject' => 'Jūsų užsakymas atšauktas',
'default_body' => 'Sveiki, {customer_name},

Jūsų užsakymas atšauktas.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Užsakymas atšauktas — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai užsakymas atšaukiamas.',
'default_subject' => 'Užsakymas atšauktas: #{booking_id}',
'default_body' => 'Užsakymas atšauktas.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Užsakymo laikas pakeistas — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai pakeičiamas užsakymo laikas.',
'default_subject' => 'Jūsų užsakymo laikas pakeistas',
'default_body' => 'Sveiki, {customer_name},

Jūsų užsakymo laikas pakeistas.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}

Atšaukti užsakymą: {cancellation_url}
Perkelti užsakymą: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Užsakymo laikas pakeistas — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai pakeičiamas užsakymo laikas.',
'default_subject' => 'Užsakymo laikas pakeistas: #{booking_id}',
'default_body' => 'Užsakymo laikas pakeistas.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Būsena: {status_label}
Mokėjimas: {payment_status_label}
Užsakymo numeris: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Užsakymas įvykdytas — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai užsakymas pažymimas įvykdytu.',
'default_subject' => 'Dėkojame, kad pasirinkote mus.',
'default_body' => 'Sveiki, {customer_name},

Dėkojame, kad pasirinkote mus. Jūsų užsakymas įvykdytas.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Užsakymas įvykdytas — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai užsakymas pažymimas įvykdytu.',
'default_subject' => 'Užsakymas įvykdytas: #{booking_id}',
'default_body' => 'Užsakymas įvykdytas.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Užsakymo numeris: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Paslauga pakeista — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai pakeičiama užsakymo paslauga.',
'default_subject' => 'Jūsų užsakymo paslauga pakeista',
'default_body' => 'Sveiki, {customer_name},

Jūsų užsakymo paslauga pakeista.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Paslauga pakeista — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai pakeičiama užsakymo paslauga.',
'default_subject' => 'Užsakymo paslauga pakeista: #{booking_id}',
'default_body' => 'Užsakymo paslauga pakeista.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Užsakymo numeris: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Laukiama mokėjimo — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai mokėjimo būsena tampa laukiama.',
'default_subject' => 'Laukiama jūsų užsakymo mokėjimo',
'default_body' => 'Sveiki, {customer_name},

Laukiama mokėjimo už jūsų užsakymą.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Mokėjimas: {payment_status_label}

Kainos suvestinė:
{price_summary}

Užsakymo numeris: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Laukiama mokėjimo — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai mokėjimo būsena tampa laukiama.',
'default_subject' => 'Laukiama mokėjimo už užsakymą #{booking_id}',
'default_body' => 'Laukiama mokėjimo.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Mokėjimas: {payment_status_label}

Kainos suvestinė:
{price_summary}

Užsakymo numeris: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Mokėjimas gautas — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai gaunamas mokėjimas.',
'default_subject' => 'Gavome jūsų mokėjimą',
'default_body' => 'Sveiki, {customer_name},

Gavome jūsų mokėjimą.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Mokėjimas: {payment_status_label}

Kainos suvestinė:
{price_summary}

Užsakymo numeris: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Mokėjimas gautas — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai gaunamas mokėjimas.',
'default_subject' => 'Gautas mokėjimas už užsakymą #{booking_id}',
'default_body' => 'Gautas mokėjimas.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Mokėjimas: {payment_status_label}

Kainos suvestinė:
{price_summary}

Užsakymo numeris: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Mokėjimas nepavyko — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai mokėjimas nepavyksta.',
'default_subject' => 'Mokėjimas už jūsų užsakymą nepavyko',
'default_body' => 'Sveiki, {customer_name},

Jūsų mokėjimo nepavyko užbaigti.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Mokėjimas nepavyko — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai mokėjimas nepavyksta.',
'default_subject' => 'Mokėjimas nepavyko: užsakymas #{booking_id}',
'default_body' => 'Mokėjimas nepavyko.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Mokėjimas: {payment_status_label}
Užsakymo numeris: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Mokėjimas grąžintas — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai mokėjimas grąžinamas.',
'default_subject' => 'Jūsų mokėjimas grąžintas',
'default_body' => 'Sveiki, {customer_name},

Jūsų mokėjimas grąžintas.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Užsakymo numeris: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Mokėjimas grąžintas — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai mokėjimas grąžinamas.',
'default_subject' => 'Mokėjimas grąžintas: užsakymas #{booking_id}',
'default_body' => 'Mokėjimas grąžintas.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Užsakymo numeris: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Sąskaita sukurta — klientui',
'description' => 'Įtraukiama į kliento siuntimo eilę, kai užsakymui sukuriama sąskaita.',
'default_subject' => 'Sukurta užsakymo #{booking_id} sąskaita',
'default_body' => 'Sveiki, {customer_name},

Jūsų užsakymui sukurta sąskaita.

Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}

Kainos suvestinė:
{price_summary}

Užsakymo numeris: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Sąskaita sukurta — administratoriui',
'description' => 'Įtraukiama į administratoriaus siuntimo eilę, kai užsakymui sukuriama sąskaita.',
'default_subject' => 'Sukurta užsakymo #{booking_id} sąskaita',
'default_body' => 'Sukurta sąskaita.

Klientas: {customer_name}
El. paštas: {customer_email}
Paslauga: {package_title}
Data: {booking_date}
Laikas: {start_time} - {end_time}
Užsakymo numeris: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Prisijungimo nuoroda — klientui',
'description' => 'Šablonas būsimiems kliento paskyros prisijungimo el. laiškams.',
'default_subject' => 'Jūsų prisijungimo nuoroda',
'default_body' => 'Sveiki, {customer_name},

Norėdami prisijungti prie paskyros, naudokite šią nuorodą:

{magic_link}

Šios nuorodos galiojimas netrukus baigsis.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Kontaktų forma — administratoriui',
'description' => 'Siunčiama administratoriui, kai lankytojas pateikia Slotera kontaktų formą.',
'default_subject' => '[{site_name}] Nauja kontaktų formos žinutė',
'default_body' => 'Gauta nauja kontaktų formos žinutė.

Vardas: {contact_name}
El. paštas: {contact_email}
Telefonas: {contact_phone}
Tema: {contact_subject}
Žinutė:
{contact_message}

Puslapis: {contact_page_title}
URL: {contact_page_url}
Pateikta: {contact_submitted_at}
Lokalė: {contact_locale}
IP: {contact_user_ip}
Naršyklė / įrenginys: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Rinkodara — akcija',
'description' => 'Pakartotinai naudojamas rinkodaros šablonas akcijoms, pasiūlymams ir pakartotinio įtraukimo el. laiškams.',
'default_subject' => '{headline}',
'default_body' => 'Sveiki, {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Specialus pasiūlymas</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Jūsų nuolaidos kodas</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · galioja iki {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'et_EE' => [
            'booking_created_customer' => [
                'title' => 'Broneering loodud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui broneering on loodud.',
                'default_subject' => 'Teie broneeringutaotlus on kätte saadud',
                'default_body' => 'Tere, {customer_name},

Täname broneeringu eest. Oleme teie taotluse kätte saanud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Staatus: {status_label}
Makse: {payment_status_label}

Hinna kokkuvõte:
{price_summary}

Broneeringu number: #{booking_id}

Tühista broneering: {cancellation_url}
Muuda broneeringut: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Uus broneering — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui uus broneering on loodud.',
                'default_subject' => 'Uus broneering on saabunud',
                'default_body' => 'Uus broneering on saabunud.

Klient: {customer_name}
E-post: {customer_email}
Telefon: {customer_phone}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Staatus: {status_label}
Makse: {payment_status_label}

Hinna kokkuvõte:
{price_summary}

Broneeringu number: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Broneering kinnitatud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui broneering kinnitatakse.',
                'default_subject' => 'Teie broneering on kinnitatud',
                'default_body' => 'Tere, {customer_name},

Teie broneering on kinnitatud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}

Tühista broneering: {cancellation_url}
Muuda broneeringut: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Broneering kinnitatud — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui broneering kinnitatakse.',
                'default_subject' => 'Broneering kinnitatud: #{booking_id}',
                'default_body' => 'Broneering on kinnitatud.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Broneeringu number: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Meeldetuletus 24h — klient',
                'description' => 'Lisatakse automaatselt järjekorda 24 tundi enne kinnitatud broneeringut.',
                'default_subject' => 'Meeldetuletus: teie broneering on homme',
                'default_body' => 'Tere, {customer_name},

Tuletame meelde teie tulevast broneeringut.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Tühista broneering: {cancellation_url}
Muuda broneeringut: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Meeldetuletus 2h — klient',
                'description' => 'Lisatakse automaatselt järjekorda 2 tundi enne kinnitatud broneeringut.',
                'default_subject' => 'Meeldetuletus: teie broneering algab varsti',
                'default_body' => 'Tere, {customer_name},

Teie broneering algab varsti.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Broneering tühistatud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui broneering tühistatakse.',
                'default_subject' => 'Teie broneering on tühistatud',
                'default_body' => 'Tere, {customer_name},

Teie broneering on tühistatud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Broneering tühistatud — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui broneering tühistatakse.',
                'default_subject' => 'Broneering tühistatud: #{booking_id}',
                'default_body' => 'Broneering on tühistatud.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Broneering muudetud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui broneering muudetakse.',
                'default_subject' => 'Teie broneering on muudetud',
                'default_body' => 'Tere, {customer_name},

Teie broneering on muudetud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}

Tühista broneering: {cancellation_url}
Muuda broneeringut: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Broneering muudetud — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui broneering muudetakse.',
                'default_subject' => 'Broneering muudetud: #{booking_id}',
                'default_body' => 'Broneering on muudetud.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Staatus: {status_label}
Makse: {payment_status_label}
Broneeringu number: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Broneering lõpetatud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui broneering märgitakse lõpetatuks.',
                'default_subject' => 'Täname, et valisite meid.',
                'default_body' => 'Tere, {customer_name},

Täname, et valisite meid. Teie broneering on nüüd lõpetatud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Broneering lõpetatud — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui broneering märgitakse lõpetatuks.',
                'default_subject' => 'Broneering lõpetatud: #{booking_id}',
                'default_body' => 'Broneering on lõpetatud.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Broneeringu number: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Teenus muudetud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui broneeringu teenust/paketti muudetakse.',
                'default_subject' => 'Teie broneeringu teenus on muudetud',
                'default_body' => 'Tere, {customer_name},

Teie broneeringu teenus on muudetud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Teenus muudetud — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui broneeringu teenust/paketti muudetakse.',
                'default_subject' => 'Broneeringu teenus muudetud: #{booking_id}',
                'default_body' => 'Broneeringu teenus on muudetud.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Broneeringu number: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Makse ootel — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui makse on ootel või vajab tegevust.',
                'default_subject' => 'Teie broneeringu makse on ootel',
                'default_body' => 'Tere, {customer_name},

Teie broneeringu makse on ootel.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Makse: {payment_status_label}

Hinna kokkuvõte:
{price_summary}

Broneeringu number: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Makse ootel — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui makse on ootel või vajab tegevust.',
                'default_subject' => 'Broneeringu #{booking_id} makse on ootel',
                'default_body' => 'Makse on ootel.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Makse: {payment_status_label}

Hinna kokkuvõte:
{price_summary}

Broneeringu number: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Makse laekunud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui makse kinnitatakse.',
                'default_subject' => 'Makse on laekunud',
                'default_body' => 'Tere, {customer_name},

Oleme teie makse kätte saanud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Makse: {payment_status_label}

Hinna kokkuvõte:
{price_summary}

Broneeringu number: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Makse laekunud — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui makse kinnitatakse.',
                'default_subject' => 'Broneeringu #{booking_id} makse on laekunud',
                'default_body' => 'Makse on laekunud.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Makse: {payment_status_label}

Hinna kokkuvõte:
{price_summary}

Broneeringu number: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Makse ebaõnnestus — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui makse ebaõnnestub.',
                'default_subject' => 'Makse ebaõnnestus',
                'default_body' => 'Tere, {customer_name},

Teie makset ei õnnestunud lõpule viia.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Makse ebaõnnestus — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui makse ebaõnnestub.',
                'default_subject' => 'Broneeringu #{booking_id} makse ebaõnnestus',
                'default_body' => 'Makse ebaõnnestus.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Makse: {payment_status_label}
Broneeringu number: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Makse tagastatud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui makse tagastatakse.',
                'default_subject' => 'Teie makse on tagastatud',
                'default_body' => 'Tere, {customer_name},

Teie makse on tagastatud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Broneeringu number: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Makse tagastatud — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui makse tagastatakse.',
                'default_subject' => 'Broneeringu #{booking_id} makse on tagastatud',
                'default_body' => 'Makse on tagastatud.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Broneeringu number: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Arve loodud — klient',
                'description' => 'Lisatakse kliendile järjekorda, kui arve luuakse.',
                'default_subject' => 'Arve broneeringu #{booking_id} eest',
                'default_body' => 'Tere, {customer_name},

Teie broneeringu jaoks on arve loodud.

Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}

Hinna kokkuvõte:
{price_summary}

Broneeringu number: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Arve loodud — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui arve luuakse.',
                'default_subject' => 'Arve loodud broneeringule #{booking_id}',
                'default_body' => 'Arve on loodud.

Klient: {customer_name}
E-post: {customer_email}
Teenus: {package_title}
Kuupäev: {booking_date}
Aeg: {start_time} - {end_time}
Broneeringu number: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Sisselogimislink — klient',
                'description' => 'Kliendi sisselogimise emaili mall.',
                'default_subject' => 'Teie sisselogimislink',
                'default_body' => 'Tere, {customer_name},

Kasutage seda linki oma kontole sisselogimiseks:

{magic_link}

Link aegub peagi.',
            ],
            'contact_form_admin' => [
                'title' => 'Kontaktvorm — administraator',
                'description' => 'Lisatakse administraatorile järjekorda, kui kontaktvorm saadetakse.',
                'default_subject' => '[{site_name}] Uus kontaktvormi sõnum',
                'default_body' => 'Uus kontaktvormi sõnum.

Nimi: {contact_name}
E-post: {contact_email}
Telefon: {contact_phone}
Teema: {contact_subject}

Sõnum:
{contact_message}

Leht: {contact_page_title}
URL: {contact_page_url}
Saadetud: {contact_submitted_at}
Keel: {contact_locale}
IP: {contact_user_ip}
Brauser / seade: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Turundus — kampaania',
                'description' => 'Korduvkasutatav turundusmall kampaaniate, pakkumiste ja klientide tagasivõitmise e-kirjade jaoks.',
                'default_subject' => '{headline}',
                'default_body' => 'Tere, {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Eripakkumine</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Teie pakkumiskood</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · kehtib kuni {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'ru_RU' => [
            'booking_created_customer' => [
                'title' => 'Бронирование создано — клиент',
                'description' => 'Добавляется в очередь для клиента при создании бронирования.',
                'default_subject' => 'Ваш запрос на бронирование получен',
                'default_body' => "Здравствуйте, {customer_name},\n\nСпасибо за бронирование. Мы получили ваш запрос.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nСтатус: {status_label}\nОплата: {payment_status_label}\n\nРасчёт стоимости:\n{price_summary}\n\nНомер бронирования: #{booking_id}\n\nОтменить бронирование: {cancellation_url}\nПеренести бронирование: {reschedule_url}",
            ],
            'booking_created_admin' => [
                'title' => 'Новое бронирование — администратор',
                'description' => 'Добавляется в очередь для администратора при создании нового бронирования.',
                'default_subject' => 'Получено новое бронирование',
                'default_body' => "Получено новое бронирование.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nТелефон: {customer_phone}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nСтатус: {status_label}\nОплата: {payment_status_label}\n\nРасчёт стоимости:\n{price_summary}\n\nНомер бронирования: #{booking_id}",
            ],
            'booking_confirmed_customer' => [
                'title' => 'Бронирование подтверждено — клиент',
                'description' => 'Добавляется в очередь для клиента при подтверждении бронирования.',
                'default_subject' => 'Ваше бронирование подтверждено',
                'default_body' => "Здравствуйте, {customer_name},\n\nВаше бронирование подтверждено.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nНомер бронирования: #{booking_id}\n\nОтменить бронирование: {cancellation_url}\nПеренести бронирование: {reschedule_url}",
            ],
            'booking_confirmed_admin' => [
                'title' => 'Бронирование подтверждено — администратор',
                'description' => 'Добавляется в очередь для администратора при подтверждении бронирования.',
                'default_subject' => 'Бронирование подтверждено: #{booking_id}',
                'default_body' => "Бронирование подтверждено.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nНомер бронирования: #{booking_id}",
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Напоминание за 24 часа — клиент',
                'description' => 'Автоматически добавляется в очередь за 24 часа до подтверждённого бронирования.',
                'default_subject' => 'Напоминание: ваше бронирование завтра',
                'default_body' => "Здравствуйте, {customer_name},\n\nНапоминаем о вашем предстоящем бронировании.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nОтменить бронирование: {cancellation_url}\nПеренести бронирование: {reschedule_url}",
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Напоминание за 2 часа — клиент',
                'description' => 'Автоматически добавляется в очередь за 2 часа до подтверждённого бронирования.',
                'default_subject' => 'Напоминание: ваше бронирование скоро начнётся',
                'default_body' => "Здравствуйте, {customer_name},\n\nВаше бронирование скоро начнётся.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}",
            ],
            'booking_cancelled_customer' => [
                'title' => 'Бронирование отменено — клиент',
                'description' => 'Добавляется в очередь для клиента при отмене бронирования.',
                'default_subject' => 'Ваше бронирование отменено',
                'default_body' => "Здравствуйте, {customer_name},\n\nВаше бронирование отменено.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nНомер бронирования: #{booking_id}",
            ],
            'booking_cancelled_admin' => [
                'title' => 'Бронирование отменено — администратор',
                'description' => 'Добавляется в очередь для администратора при отмене бронирования.',
                'default_subject' => 'Бронирование отменено: #{booking_id}',
                'default_body' => "Бронирование отменено.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nНомер бронирования: #{booking_id}",
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Бронирование перенесено — клиент',
                'description' => 'Добавляется в очередь для клиента при переносе бронирования.',
                'default_subject' => 'Ваше бронирование перенесено',
                'default_body' => "Здравствуйте, {customer_name},\n\nВаше бронирование перенесено.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nНомер бронирования: #{booking_id}\n\nОтменить бронирование: {cancellation_url}\nПеренести бронирование: {reschedule_url}",
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Бронирование перенесено — администратор',
                'description' => 'Добавляется в очередь для администратора при переносе бронирования.',
                'default_subject' => 'Бронирование перенесено: #{booking_id}',
                'default_body' => "Бронирование перенесено.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nСтатус: {status_label}\nОплата: {payment_status_label}\nНомер бронирования: #{booking_id}",
            ],
            'booking_completed_customer' => [
                'title' => 'Бронирование завершено — клиент',
                'description' => 'Добавляется в очередь для клиента, когда бронирование отмечено как завершённое.',
                'default_subject' => 'Спасибо, что выбрали нас.',
                'default_body' => "Здравствуйте, {customer_name},\n\nСпасибо, что выбрали нас. Ваше бронирование завершено.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nНомер бронирования: #{booking_id}",
            ],
            'booking_completed_admin' => [
                'title' => 'Бронирование завершено — администратор',
                'description' => 'Добавляется в очередь для администратора, когда бронирование отмечено как завершённое.',
                'default_subject' => 'Бронирование завершено: #{booking_id}',
                'default_body' => "Бронирование завершено.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nНомер бронирования: #{booking_id}",
            ],
            'package_changed_customer' => [
                'title' => 'Услуга изменена — клиент',
                'description' => 'Добавляется в очередь для клиента при изменении услуги/пакета в бронировании.',
                'default_subject' => 'Услуга в вашем бронировании изменена',
                'default_body' => "Здравствуйте, {customer_name},\n\nУслуга в вашем бронировании была изменена.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nНомер бронирования: #{booking_id}",
            ],
            'package_changed_admin' => [
                'title' => 'Услуга изменена — администратор',
                'description' => 'Добавляется в очередь для администратора при изменении услуги/пакета в бронировании.',
                'default_subject' => 'Услуга в бронировании изменена: #{booking_id}',
                'default_body' => "Услуга в бронировании была изменена.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nНомер бронирования: #{booking_id}",
            ],
            'payment_pending_customer' => [
                'title' => 'Ожидается оплата — клиент',
                'description' => 'Добавляется в очередь для клиента, когда оплата ожидается или требует действия.',
                'default_subject' => 'Ожидается оплата вашего бронирования',
                'default_body' => "Здравствуйте, {customer_name},\n\nОплата вашего бронирования ожидается.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nОплата: {payment_status_label}\n\nРасчёт стоимости:\n{price_summary}\n\nНомер бронирования: #{booking_id}",
            ],
            'payment_pending_admin' => [
                'title' => 'Ожидается оплата — администратор',
                'description' => 'Добавляется в очередь для администратора, когда оплата ожидается или требует действия.',
                'default_subject' => 'Ожидается оплата бронирования #{booking_id}',
                'default_body' => "Ожидается оплата.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nОплата: {payment_status_label}\n\nРасчёт стоимости:\n{price_summary}\n\nНомер бронирования: #{booking_id}",
            ],
            'payment_received_customer' => [
                'title' => 'Оплата получена — клиент',
                'description' => 'Добавляется в очередь для клиента при подтверждении оплаты.',
                'default_subject' => 'Оплата получена',
                'default_body' => "Здравствуйте, {customer_name},\n\nМы получили вашу оплату.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nОплата: {payment_status_label}\n\nРасчёт стоимости:\n{price_summary}\n\nНомер бронирования: #{booking_id}",
            ],
            'payment_received_admin' => [
                'title' => 'Оплата получена — администратор',
                'description' => 'Добавляется в очередь для администратора при подтверждении оплаты.',
                'default_subject' => 'Оплата получена для бронирования #{booking_id}',
                'default_body' => "Оплата получена.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nОплата: {payment_status_label}\n\nРасчёт стоимости:\n{price_summary}\n\nНомер бронирования: #{booking_id}",
            ],
            'payment_failed_customer' => [
                'title' => 'Оплата не прошла — клиент',
                'description' => 'Добавляется в очередь для клиента при неудачной оплате.',
                'default_subject' => 'Оплата не прошла',
                'default_body' => "Здравствуйте, {customer_name},\n\nНе удалось завершить вашу оплату.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nНомер бронирования: #{booking_id}",
            ],
            'payment_failed_admin' => [
                'title' => 'Оплата не прошла — администратор',
                'description' => 'Добавляется в очередь для администратора при неудачной оплате.',
                'default_subject' => 'Оплата не прошла для бронирования #{booking_id}',
                'default_body' => "Оплата не прошла.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nОплата: {payment_status_label}\nНомер бронирования: #{booking_id}",
            ],
            'payment_refunded_customer' => [
                'title' => 'Возврат средств — клиент',
                'description' => 'Добавляется в очередь для клиента при возврате оплаты.',
                'default_subject' => 'Средства возвращены',
                'default_body' => "Здравствуйте, {customer_name},\n\nСредства по вашему платежу возвращены.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nНомер бронирования: #{booking_id}",
            ],
            'payment_refunded_admin' => [
                'title' => 'Возврат средств — администратор',
                'description' => 'Добавляется в очередь для администратора при возврате оплаты.',
                'default_subject' => 'Возврат средств по бронированию #{booking_id}',
                'default_body' => "Средства возвращены.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nНомер бронирования: #{booking_id}",
            ],
            'invoice_created_customer' => [
                'title' => 'Счёт создан — клиент',
                'description' => 'Добавляется в очередь для клиента при создании счёта.',
                'default_subject' => 'Счёт по бронированию #{booking_id}',
                'default_body' => "Здравствуйте, {customer_name},\n\nДля вашего бронирования создан счёт.\n\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\n\nРасчёт стоимости:\n{price_summary}\n\nНомер бронирования: #{booking_id}",
            ],
            'invoice_created_admin' => [
                'title' => 'Счёт создан — администратор',
                'description' => 'Добавляется в очередь для администратора при создании счёта.',
                'default_subject' => 'Счёт создан для бронирования #{booking_id}',
                'default_body' => "Счёт создан.\n\nКлиент: {customer_name}\nЭл. почта: {customer_email}\nУслуга: {package_title}\nДата: {booking_date}\nВремя: {start_time} - {end_time}\nНомер бронирования: #{booking_id}",
            ],
            'magic_link_customer' => [
                'title' => 'Ссылка для входа — клиент',
                'description' => 'Шаблон для будущих писем со ссылкой для входа клиента.',
                'default_subject' => 'Ваша ссылка для входа',
                'default_body' => "Здравствуйте, {customer_name},\n\nИспользуйте эту ссылку для входа в аккаунт:\n\n{magic_link}\n\nСрок действия ссылки скоро истечёт.",
            ],
            'contact_form_admin' => [
                'title' => 'Контактная форма — администратор',
                'description' => 'Отправляется администратору, когда посетитель отправляет контактную форму Slotera.',
                'default_subject' => '[{site_name}] Новое сообщение из контактной формы',
                'default_body' => "Новое сообщение из контактной формы.\n\nИмя: {contact_name}\nЭл. почта: {contact_email}\nТелефон: {contact_phone}\nТема: {contact_subject}\nСообщение:\n{contact_message}\n\nСтраница: {contact_page_title}\nURL: {contact_page_url}\nОтправлено: {contact_submitted_at}\nЯзык: {contact_locale}\nIP: {contact_user_ip}\nБраузер / устройство: {contact_user_agent}",
            ],
            'marketing_promo' => [
                'title' => 'Маркетинг — промо',
                'description' => 'Многоразовый маркетинговый шаблон для промокампаний, специальных предложений и писем для возврата клиентов.',
                'default_subject' => '{headline}',
                'default_body' => "Здравствуйте, {customer_name},\n\n{headline}\n\n{message}\n\n{submessage}\n\n{coupon_code}\n\n{cta_url}",
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Специальное предложение</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Ваш промокод</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · действует до {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'da_DK' => [
            'booking_created_customer' => [
                'title' => 'Booking oprettet — kunde',
                'description' => 'Sættes i kø til kunden, når en booking oprettes.',
                'default_subject' => 'Din bookinganmodning er modtaget',
                'default_body' => 'Hej {customer_name},

Tak for din booking. Vi har modtaget din anmodning.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bookingstatus: {status_label}
Betaling: {payment_status_label}

Prisoversigt:
{price_summary}

Bookingnummer: #{booking_id}

Afbestil booking: {cancellation_url}
Ombook booking: {reschedule_url}',
            ],
            'booking_created_admin' => [
                'title' => 'Ny booking — administrator',
                'description' => 'Sættes i kø til administratoren, når en ny booking oprettes.',
                'default_subject' => 'Ny booking modtaget',
                'default_body' => 'Ny booking modtaget.

Kunde: {customer_name}
E-mail: {customer_email}
Telefon: {customer_phone}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bookingstatus: {status_label}
Betaling: {payment_status_label}

Prisoversigt:
{price_summary}

Bookingnummer: #{booking_id}',
            ],
            'booking_confirmed_customer' => [
                'title' => 'Booking bekræftet — kunde',
                'description' => 'Sættes i kø til kunden, når en booking bekræftes.',
                'default_subject' => 'Din booking er bekræftet',
                'default_body' => 'Hej {customer_name},

Din booking er bekræftet.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}

Afbestil booking: {cancellation_url}
Ombook booking: {reschedule_url}',
            ],
            'booking_confirmed_admin' => [
                'title' => 'Booking bekræftet — administrator',
                'description' => 'Sættes i kø til administratoren, når en booking bekræftes.',
                'default_subject' => 'Booking bekræftet: #{booking_id}',
                'default_body' => 'En booking er blevet bekræftet.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bookingnummer: #{booking_id}',
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Påmindelse 24 timer — kunde',
                'description' => 'Sættes automatisk i kø 24 timer før en bekræftet booking.',
                'default_subject' => 'Påmindelse: Din booking er i morgen',
                'default_body' => 'Hej {customer_name},

Dette er en påmindelse om din kommende booking.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Afbestil booking: {cancellation_url}
Ombook booking: {reschedule_url}',
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Påmindelse 2 timer — kunde',
                'description' => 'Sættes automatisk i kø 2 timer før en bekræftet booking.',
                'default_subject' => 'Påmindelse: Din booking begynder snart',
                'default_body' => 'Hej {customer_name},

Din booking starter snart.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}',
            ],
            'booking_cancelled_customer' => [
                'title' => 'Booking annulleret — kunde',
                'description' => 'Sættes i kø til kunden, når en booking annulleres.',
                'default_subject' => 'Din booking er annulleret',
                'default_body' => 'Hej {customer_name},

Din booking er blevet annulleret.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}',
            ],
            'booking_cancelled_admin' => [
                'title' => 'Booking annulleret — administrator',
                'description' => 'Sættes i kø til administratoren, når en booking annulleres.',
                'default_subject' => 'Booking annulleret: #{booking_id}',
                'default_body' => 'En booking er blevet annulleret.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}',
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Booking ombooket — kunde',
                'description' => 'Sættes i kø til kunden, når en booking ombookes.',
                'default_subject' => 'Din booking er ombooket',
                'default_body' => 'Hej {customer_name},

Din booking er blevet ombooket.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}

Afbestil booking: {cancellation_url}
Ombook booking: {reschedule_url}',
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Booking ombooket — administrator',
                'description' => 'Sættes i kø til administratoren, når en booking ombookes.',
                'default_subject' => 'Booking ombooket: #{booking_id}',
                'default_body' => 'En booking er blevet ombooket.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bookingstatus: {status_label}
Betaling: {payment_status_label}
Bookingnummer: #{booking_id}',
            ],
            'booking_completed_customer' => [
                'title' => 'Booking gennemført — kunde',
                'description' => 'Sættes i kø til kunden, når en booking markeres som gennemført.',
                'default_subject' => 'Tak, fordi du valgte os.',
                'default_body' => 'Hej {customer_name},

Tak, fordi du valgte os. Din booking er nu gennemført.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}',
            ],
            'booking_completed_admin' => [
                'title' => 'Booking gennemført — administrator',
                'description' => 'Sættes i kø til administratoren, når en booking markeres som gennemført.',
                'default_subject' => 'Booking gennemført: #{booking_id}',
                'default_body' => 'En booking er blevet gennemført.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bookingnummer: #{booking_id}',
            ],
            'package_changed_customer' => [
                'title' => 'Pakke ændret — kunde',
                'description' => 'Sættes i kø til kunden, når bookingens ydelse eller pakke ændres.',
                'default_subject' => 'Ydelsen i din booking er ændret',
                'default_body' => 'Hej {customer_name},

Ydelsen i din booking er blevet ændret.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}',
            ],
            'package_changed_admin' => [
                'title' => 'Pakke ændret — administrator',
                'description' => 'Sættes i kø til administratoren, når bookingens ydelse eller pakke ændres.',
                'default_subject' => 'Bookingydelse ændret: #{booking_id}',
                'default_body' => 'Ydelsen i en booking er blevet ændret.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bookingnummer: #{booking_id}',
            ],
            'payment_pending_customer' => [
                'title' => 'Betaling afventer — kunde',
                'description' => 'Sættes i kø til kunden, når betaling afventer eller kræver handling.',
                'default_subject' => 'Betaling afventer for din booking',
                'default_body' => 'Hej {customer_name},

Betalingen for din booking afventer.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}

Prisoversigt:
{price_summary}

Bookingnummer: #{booking_id}',
            ],
            'payment_pending_admin' => [
                'title' => 'Betaling afventer — administrator',
                'description' => 'Sættes i kø til administratoren, når betaling afventer eller kræver handling.',
                'default_subject' => 'Betaling afventer for booking #{booking_id}',
                'default_body' => 'Betalingen afventer.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}

Prisoversigt:
{price_summary}

Bookingnummer: #{booking_id}',
            ],
            'payment_received_customer' => [
                'title' => 'Betalingsbekræftelse — kunde',
                'description' => 'Sættes i kø til kunden, når betalingen bekræftes.',
                'default_subject' => 'Betaling modtaget',
                'default_body' => 'Hej {customer_name},

Vi har modtaget din betaling.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}

Prisoversigt:
{price_summary}

Bookingnummer: #{booking_id}',
            ],
            'payment_received_admin' => [
                'title' => 'Betalingsbekræftelse — administrator',
                'description' => 'Sættes i kø til administratoren, når betalingen bekræftes.',
                'default_subject' => 'Betaling modtaget for booking #{booking_id}',
                'default_body' => 'Betaling modtaget.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}

Prisoversigt:
{price_summary}

Bookingnummer: #{booking_id}',
            ],
            'payment_failed_customer' => [
                'title' => 'Betaling mislykkedes — kunde',
                'description' => 'Sættes i kø til kunden, når betalingen mislykkes.',
                'default_subject' => 'Betalingen mislykkedes',
                'default_body' => 'Hej {customer_name},

Din betaling kunne ikke gennemføres.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}',
            ],
            'payment_failed_admin' => [
                'title' => 'Betaling mislykkedes — administrator',
                'description' => 'Sættes i kø til administratoren, når betalingen mislykkes.',
                'default_subject' => 'Betaling mislykkedes for booking #{booking_id}',
                'default_body' => 'Betalingen mislykkedes.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Betaling: {payment_status_label}
Bookingnummer: #{booking_id}',
            ],
            'payment_refunded_customer' => [
                'title' => 'Betaling refunderet — kunde',
                'description' => 'Sættes i kø til kunden, når betalingen refunderes.',
                'default_subject' => 'Din betaling er refunderet',
                'default_body' => 'Hej {customer_name},

Din betaling er blevet refunderet.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Bookingnummer: #{booking_id}',
            ],
            'payment_refunded_admin' => [
                'title' => 'Betaling refunderet — administrator',
                'description' => 'Sættes i kø til administratoren, når betalingen refunderes.',
                'default_subject' => 'Betaling refunderet for booking #{booking_id}',
                'default_body' => 'Betalingen er refunderet.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bookingnummer: #{booking_id}',
            ],
            'invoice_created_customer' => [
                'title' => 'Faktura oprettet — kunde',
                'description' => 'Sættes i kø til kunden, når en faktura oprettes.',
                'default_subject' => 'Faktura for booking #{booking_id}',
                'default_body' => 'Hej {customer_name},

Der er oprettet en faktura til din booking.

Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}

Prisoversigt:
{price_summary}

Bookingnummer: #{booking_id}',
            ],
            'invoice_created_admin' => [
                'title' => 'Faktura oprettet — administrator',
                'description' => 'Sættes i kø til administratoren, når en faktura oprettes.',
                'default_subject' => 'Faktura oprettet for booking #{booking_id}',
                'default_body' => 'Der er oprettet en faktura.

Kunde: {customer_name}
E-mail: {customer_email}
Ydelse: {package_title}
Dato: {booking_date}
Tid: {start_time} - {end_time}
Bookingnummer: #{booking_id}',
            ],
            'magic_link_customer' => [
                'title' => 'Magic link — kunde',
                'description' => 'Skabelon til fremtidige e-mails med kundelogin.',
                'default_subject' => 'Dit loginlink',
                'default_body' => 'Hej {customer_name},

Brug dette link til at logge ind på din konto:

{magic_link}

Dette link udløber snart.',
            ],
            'contact_form_admin' => [
                'title' => 'Kontaktformular — administrator',
                'description' => 'Sendes til administratoren, når en besøgende indsender Sloteras kontaktformular.',
                'default_subject' => '[{site_name}] Ny kontaktbesked',
                'default_body' => 'Ny besked fra kontaktformularen.

Navn: {contact_name}
E-mail: {contact_email}
Telefon: {contact_phone}
Emne: {contact_subject}
Besked:
{contact_message}

Side: {contact_page_title}
URL: {contact_page_url}
Indsendt: {contact_submitted_at}
Sprogkode: {contact_locale}
IP: {contact_user_ip}
Brugeragent: {contact_user_agent}',
            ],
            'marketing_promo' => [
                'title' => 'Marketing — kampagne',
                'description' => 'Genanvendelig marketingskabelon til kampagner, tilbud og kom tilbage-e-mails.',
                'default_subject' => '{headline}',
                'default_body' => 'Hej {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Særtilbud</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Din tilbudskode</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · gyldig til {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'nl_NL' => [
            'booking_created_customer' => [
                'title' => 'Boeking aangemaakt — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een boeking wordt aangemaakt.',
                'default_subject' => 'Uw boekingsaanvraag is ontvangen',
                'default_body' => "Hallo {customer_name},\n\nBedankt voor je boeking. We hebben je aanvraag ontvangen.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBoekingsstatus: {status_label}\nBetaling: {payment_status_label}\n\nPrijsoverzicht:\n{price_summary}\n\nBoekingsnummer: #{booking_id}\n\nBoeking annuleren: {cancellation_url}\nBoeking verplaatsen: {reschedule_url}",
            ],
            'booking_created_admin' => [
                'title' => 'Nieuwe boeking — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een nieuwe boeking wordt aangemaakt.',
                'default_subject' => 'Nieuwe boeking ontvangen',
                'default_body' => "Er is een nieuwe boeking ontvangen.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nTelefoon: {customer_phone}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBoekingsstatus: {status_label}\nBetaling: {payment_status_label}\n\nPrijsoverzicht:\n{price_summary}\n\nBoekingsnummer: #{booking_id}",
            ],
            'booking_confirmed_customer' => [
                'title' => 'Boeking bevestigd — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een boeking wordt bevestigd.',
                'default_subject' => 'Uw boeking is bevestigd',
                'default_body' => "Hallo {customer_name},\n\nUw boeking is bevestigd.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoekingsnummer: #{booking_id}\n\nBoeking annuleren: {cancellation_url}\nBoeking verplaatsen: {reschedule_url}",
            ],
            'booking_confirmed_admin' => [
                'title' => 'Boeking bevestigd — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een boeking wordt bevestigd.',
                'default_subject' => 'Boeking bevestigd: #{booking_id}',
                'default_body' => "Een boeking is bevestigd.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBoekingsnummer: #{booking_id}",
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Herinnering 24 uur — klant',
                'description' => 'Automatisch in de wachtrij gezet 24 uur vóór een bevestigde boeking.',
                'default_subject' => 'Herinnering: uw boeking is morgen',
                'default_body' => "Hallo {customer_name},\n\nDit is een herinnering voor uw aankomende boeking.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoeking annuleren: {cancellation_url}\nBoeking verplaatsen: {reschedule_url}",
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Herinnering 2 uur — klant',
                'description' => 'Automatisch in de wachtrij gezet 2 uur vóór een bevestigde boeking.',
                'default_subject' => 'Herinnering: uw boeking begint binnenkort',
                'default_body' => "Hallo {customer_name},\n\nUw boeking begint binnenkort.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}",
            ],
            'booking_cancelled_customer' => [
                'title' => 'Boeking geannuleerd — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een boeking wordt geannuleerd.',
                'default_subject' => 'Uw boeking is geannuleerd',
                'default_body' => "Hallo {customer_name},\n\nUw boeking is geannuleerd.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoekingsnummer: #{booking_id}",
            ],
            'booking_cancelled_admin' => [
                'title' => 'Boeking geannuleerd — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een boeking wordt geannuleerd.',
                'default_subject' => 'Boeking geannuleerd: #{booking_id}',
                'default_body' => "Een boeking is geannuleerd.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoekingsnummer: #{booking_id}",
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Boeking verplaatst — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een boeking wordt verplaatst.',
                'default_subject' => 'Uw boeking is verplaatst',
                'default_body' => "Hallo {customer_name},\n\nUw boeking is verplaatst.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoekingsnummer: #{booking_id}\n\nBoeking annuleren: {cancellation_url}\nBoeking verplaatsen: {reschedule_url}",
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Boeking verplaatst — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een boeking wordt verplaatst.',
                'default_subject' => 'Boeking verplaatst: #{booking_id}',
                'default_body' => "Een boeking is verplaatst.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBoekingsstatus: {status_label}\nBetaling: {payment_status_label}\nBoekingsnummer: #{booking_id}",
            ],
            'booking_completed_customer' => [
                'title' => 'Boeking voltooid — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een boeking als voltooid wordt gemarkeerd.',
                'default_subject' => 'Bedankt dat u voor ons heeft gekozen.',
                'default_body' => "Hallo {customer_name},\n\nBedankt dat u voor ons heeft gekozen. Uw boeking is nu voltooid.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoekingsnummer: #{booking_id}",
            ],
            'booking_completed_admin' => [
                'title' => 'Boeking voltooid — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een boeking als voltooid wordt gemarkeerd.',
                'default_subject' => 'Boeking voltooid: #{booking_id}',
                'default_body' => "Een boeking is voltooid.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBoekingsnummer: #{booking_id}",
            ],
            'package_changed_customer' => [
                'title' => 'Dienst gewijzigd — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer de dienst of het pakket van de boeking wordt gewijzigd.',
                'default_subject' => 'De dienst van uw boeking is gewijzigd',
                'default_body' => "Hallo {customer_name},\n\nDe dienst voor uw boeking is gewijzigd.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoekingsnummer: #{booking_id}",
            ],
            'package_changed_admin' => [
                'title' => 'Dienst gewijzigd — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer de dienst of het pakket van de boeking wordt gewijzigd.',
                'default_subject' => 'Boekingsdienst gewijzigd: #{booking_id}',
                'default_body' => "De dienst voor een boeking is gewijzigd.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBoekingsnummer: #{booking_id}",
            ],
            'payment_pending_customer' => [
                'title' => 'Betaling in afwachting — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een betaling in afwachting is of actie vereist.',
                'default_subject' => 'Betaling voor uw boeking is in afwachting',
                'default_body' => "Hallo {customer_name},\n\nDe betaling voor uw boeking is in afwachting.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBetaling: {payment_status_label}\n\nPrijsoverzicht:\n{price_summary}\n\nBoekingsnummer: #{booking_id}",
            ],
            'payment_pending_admin' => [
                'title' => 'Betaling in afwachting — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een betaling in afwachting is of actie vereist.',
                'default_subject' => 'Betaling in afwachting voor boeking #{booking_id}',
                'default_body' => "De betaling is in afwachting.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBetaling: {payment_status_label}\n\nPrijsoverzicht:\n{price_summary}\n\nBoekingsnummer: #{booking_id}",
            ],
            'payment_received_customer' => [
                'title' => 'Betalingsbevestiging — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een betaling wordt bevestigd.',
                'default_subject' => 'Betaling ontvangen',
                'default_body' => "Hallo {customer_name},\n\nWe hebben uw betaling ontvangen.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBetaling: {payment_status_label}\n\nPrijsoverzicht:\n{price_summary}\n\nBoekingsnummer: #{booking_id}",
            ],
            'payment_received_admin' => [
                'title' => 'Betalingsbevestiging — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een betaling wordt bevestigd.',
                'default_subject' => 'Betaling ontvangen voor boeking #{booking_id}',
                'default_body' => "Betaling ontvangen.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBetaling: {payment_status_label}\n\nPrijsoverzicht:\n{price_summary}\n\nBoekingsnummer: #{booking_id}",
            ],
            'payment_failed_customer' => [
                'title' => 'Betaling mislukt — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een betaling mislukt.',
                'default_subject' => 'Betaling mislukt',
                'default_body' => "Hallo {customer_name},\n\nUw betaling kon niet worden voltooid.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoekingsnummer: #{booking_id}",
            ],
            'payment_failed_admin' => [
                'title' => 'Betaling mislukt — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een betaling mislukt.',
                'default_subject' => 'Betaling mislukt voor boeking #{booking_id}',
                'default_body' => "Betaling mislukt.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBetaling: {payment_status_label}\nBoekingsnummer: #{booking_id}",
            ],
            'payment_refunded_customer' => [
                'title' => 'Betaling terugbetaald — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een betaling wordt terugbetaald.',
                'default_subject' => 'Uw betaling is terugbetaald',
                'default_body' => "Hallo {customer_name},\n\nUw betaling is terugbetaald.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nBoekingsnummer: #{booking_id}",
            ],
            'payment_refunded_admin' => [
                'title' => 'Betaling terugbetaald — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een betaling wordt terugbetaald.',
                'default_subject' => 'Betaling terugbetaald voor boeking #{booking_id}',
                'default_body' => "Betaling terugbetaald.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBoekingsnummer: #{booking_id}",
            ],
            'invoice_created_customer' => [
                'title' => 'Factuur aangemaakt — klant',
                'description' => 'In de wachtrij gezet voor de klant wanneer een factuur wordt aangemaakt.',
                'default_subject' => 'Factuur voor boeking #{booking_id}',
                'default_body' => "Hallo {customer_name},\n\nEr is een factuur aangemaakt voor uw boeking.\n\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\n\nPrijsoverzicht:\n{price_summary}\n\nBoekingsnummer: #{booking_id}",
            ],
            'invoice_created_admin' => [
                'title' => 'Factuur aangemaakt — beheerder',
                'description' => 'In de wachtrij gezet voor de beheerder wanneer een factuur wordt aangemaakt.',
                'default_subject' => 'Factuur aangemaakt voor boeking #{booking_id}',
                'default_body' => "Er is een factuur aangemaakt.\n\nKlant: {customer_name}\nE-mail: {customer_email}\nDienst: {package_title}\nDatum: {booking_date}\nTijd: {start_time} - {end_time}\nBoekingsnummer: #{booking_id}",
            ],
            'magic_link_customer' => [
                'title' => 'Inloglink — klant',
                'description' => 'Sjabloon voor toekomstige inlogmails voor klanten.',
                'default_subject' => 'Uw inloglink',
                'default_body' => "Hallo {customer_name},\n\nGebruik deze link om in te loggen op uw account:\n\n{magic_link}\n\nDeze link verloopt binnenkort.",
            ],
            'contact_form_admin' => [
                'title' => 'Contactformulier — beheerder',
                'description' => 'Verzonden naar de beheerder wanneer een bezoeker het Slotera-contactformulier indient.',
                'default_subject' => '[{site_name}] Nieuw contactbericht',
                'default_body' => "Nieuw bericht via het contactformulier.\n\nNaam: {contact_name}\nE-mail: {contact_email}\nTelefoon: {contact_phone}\nOnderwerp: {contact_subject}\nBericht:\n{contact_message}\n\nPagina: {contact_page_title}\nURL: {contact_page_url}\nIngediend: {contact_submitted_at}\nTaalinstelling: {contact_locale}\nIP: {contact_user_ip}\nBrowser/apparaat: {contact_user_agent}",
            ],
            'marketing_promo' => [
                'title' => 'Marketing — promotie',
                'description' => 'Herbruikbaar marketingsjabloon voor promotiecampagnes, aanbiedingen en terugwinmails.',
                'default_subject' => '{headline}',
                'default_body' => "Hallo {customer_name},\n\n{headline}\n\n{message}\n\n{submessage}\n\n{coupon_code}\n\n{cta_url}",
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Speciale aanbieding</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Uw kortingscode</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · geldig tot {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'de_DE' => [
            'booking_created_customer' => [
                'title' => 'Buchungsbestätigung',
                'description' => 'Wird in die Warteschlange gestellt, wenn eine Buchung erstellt wird.',
                'default_subject' => 'Ihre Buchungsbestätigung',
                'default_body' => "Hallo {customer_name},\n\nvielen Dank für Ihre Buchung.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nBuchungsstatus: {status_label}\nZahlung: {payment_status_label}\n\nPreisübersicht:\n{price_summary}\n\nBuchungsnummer: #{booking_id}\n\nBuchung stornieren: {cancellation_url}\nBuchung verschieben: {reschedule_url}",
            ],
            'booking_created_admin' => [
                'title' => 'Neue Buchung',
                'description' => 'Wird an den Admin gesendet, wenn eine neue Buchung erstellt wird.',
                'default_subject' => 'Neue Buchung eingegangen',
                'default_body' => "Neue Buchung eingegangen.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nTelefon: {customer_phone}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nBuchungsstatus: {status_label}\nZahlung: {payment_status_label}\n\nPreisübersicht:\n{price_summary}\n\nBuchungsnummer: #{booking_id}",
            ],
            'booking_reminder_24h_customer' => [
                'title' => 'Erinnerung 24 Std.',
                'description' => 'Wird automatisch 24 Stunden vor einer bestätigten Buchung in die Warteschlange gestellt.',
                'default_subject' => 'Erinnerung: Ihre Buchung ist morgen',
                'default_body' => "Hallo {customer_name},\n\ndies ist eine Erinnerung an Ihre bevorstehende Buchung.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchung stornieren: {cancellation_url}\nBuchung verschieben: {reschedule_url}",
            ],
            'booking_reminder_2h_customer' => [
                'title' => 'Erinnerung 2 Std.',
                'description' => 'Wird automatisch 2 Stunden vor einer bestätigten Buchung in die Warteschlange gestellt.',
                'default_subject' => 'Erinnerung: Ihre Buchung beginnt bald',
                'default_body' => "Hallo {customer_name},\n\nIhre Buchung beginnt bald.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}",
            ],
            'booking_cancelled_customer' => [
                'title' => 'Buchung storniert',
                'description' => 'Wird in die Warteschlange gestellt, wenn eine Buchung storniert wird.',
                'default_subject' => 'Ihre Buchung wurde storniert',
                'default_body' => "Hallo {customer_name},\n\nIhre Buchung wurde storniert.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchungsnummer: #{booking_id}",
            ],
            'booking_cancelled_admin' => [
                'title' => 'Buchung storniert',
                'description' => 'Wird an den Admin gesendet, wenn eine Buchung storniert wird.',
                'default_subject' => 'Buchung storniert: #{booking_id}',
                'default_body' => "Eine Buchung wurde storniert.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchungsnummer: #{booking_id}",
            ],
            'booking_rescheduled_customer' => [
                'title' => 'Buchung verschoben',
                'description' => 'Wird an den Kunden gesendet, wenn eine Buchung verschoben wird.',
                'default_subject' => 'Ihre Buchung wurde verschoben',
                'default_body' => "Hallo {customer_name},\n\nIhre Buchung wurde verschoben.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchungsnummer: #{booking_id}\n\nBuchung stornieren: {cancellation_url}\nBuchung erneut verschieben: {reschedule_url}",
            ],
            'package_changed_customer' => [
                'title' => 'Paket geändert',
                'description' => 'Vorlage für zukünftige E-Mails bei geänderter Leistung.',
                'default_subject' => 'Die Leistung Ihrer Buchung wurde geändert',
                'default_body' => "Hallo {customer_name},\n\ndie Leistung für Ihre Buchung wurde geändert.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchungsnummer: #{booking_id}",
            ],
            'booking_rescheduled_admin' => [
                'title' => 'Buchung verschoben',
                'description' => 'Wird an den Admin gesendet, wenn eine Buchung verschoben wird.',
                'default_subject' => 'Buchung verschoben: #{booking_id}',
                'default_body' => "Eine Buchung wurde verschoben.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nBuchungsstatus: {status_label}\nZahlung: {payment_status_label}\nBuchungsnummer: #{booking_id}",
            ],
            'payment_received_customer' => [
                'title' => 'Zahlungsbestätigung',
                'description' => 'Wird in die Warteschlange gestellt, wenn eine Zahlung bestätigt wurde.',
                'default_subject' => 'Zahlung erhalten',
                'default_body' => "Hallo {customer_name},\n\nwir haben Ihre Zahlung erhalten.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nZahlung: {payment_status_label}\n\nPreisübersicht:\n{price_summary}\n\nBuchungsnummer: #{booking_id}",
            ],
            'payment_received_admin' => [
                'title' => 'Zahlungsbestätigung',
                'description' => 'Wird an den Admin gesendet, wenn eine Zahlung bestätigt wurde.',
                'default_subject' => 'Zahlung für Buchung #{booking_id} erhalten',
                'default_body' => "Zahlung erhalten.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nZahlung: {payment_status_label}\n\nPreisübersicht:\n{price_summary}\n\nBuchungsnummer: #{booking_id}",
            ],
            'payment_failed_customer' => [
                'title' => 'Zahlung fehlgeschlagen',
                'description' => 'Vorlage für zukünftige E-Mails bei fehlgeschlagener Zahlung.',
                'default_subject' => 'Zahlung fehlgeschlagen',
                'default_body' => "Hallo {customer_name},\n\nIhre Zahlung konnte nicht abgeschlossen werden.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchungsnummer: #{booking_id}",
            ],
            'booking_confirmed_customer' => [
                'title' => 'Buchung bestätigt — Kunde',
                'description' => 'Wird für den Kunden in die Warteschlange gestellt, wenn eine Buchung bestätigt wird.',
                'default_subject' => 'Ihre Buchung ist bestätigt',
                'default_body' => "Hallo {customer_name},\n\nIhre Buchung ist bestätigt.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchungsnummer: #{booking_id}\n\nBuchung stornieren: {cancellation_url}\nBuchung verschieben: {reschedule_url}",
            ],
            'booking_confirmed_admin' => [
                'title' => 'Buchung bestätigt — Admin',
                'description' => 'Wird für den Admin in die Warteschlange gestellt, wenn eine Buchung bestätigt wird.',
                'default_subject' => 'Buchung bestätigt: #{booking_id}',
                'default_body' => "Eine Buchung wurde bestätigt.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nBuchungsnummer: #{booking_id}",
            ],
            'booking_completed_customer' => [
                'title' => 'Buchung abgeschlossen — Kunde',
                'description' => 'Wird für den Kunden in die Warteschlange gestellt, wenn eine Buchung als abgeschlossen markiert wird.',
                'default_subject' => 'Vielen Dank, dass Sie sich für uns entschieden haben.',
                'default_body' => "Hallo {customer_name},\n\nvielen Dank, dass Sie sich für uns entschieden haben. Ihre Buchung ist nun abgeschlossen.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchungsnummer: #{booking_id}",
            ],
            'booking_completed_admin' => [
                'title' => 'Buchung abgeschlossen — Admin',
                'description' => 'Wird für den Admin in die Warteschlange gestellt, wenn eine Buchung als abgeschlossen markiert wird.',
                'default_subject' => 'Buchung abgeschlossen: #{booking_id}',
                'default_body' => "Eine Buchung wurde abgeschlossen.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nBuchungsnummer: #{booking_id}",
            ],
            'package_changed_admin' => [
                'title' => 'Paket geändert — Admin',
                'description' => 'Wird für den Admin in die Warteschlange gestellt, wenn die Leistung oder das Paket einer Buchung geändert wird.',
                'default_subject' => 'Buchungsleistung geändert: #{booking_id}',
                'default_body' => "Die Leistung einer Buchung wurde geändert.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nBuchungsnummer: #{booking_id}",
            ],
            'payment_pending_customer' => [
                'title' => 'Zahlung ausstehend — Kunde',
                'description' => 'Wird für den Kunden in die Warteschlange gestellt, wenn eine Zahlung aussteht oder eine Aktion erforderlich ist.',
                'default_subject' => 'Die Zahlung für Ihre Buchung steht noch aus',
                'default_body' => "Hallo {customer_name},\n\ndie Zahlung für Ihre Buchung steht noch aus.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nZahlung: {payment_status_label}\n\nPreisübersicht:\n{price_summary}\n\nBuchungsnummer: #{booking_id}",
            ],
            'payment_pending_admin' => [
                'title' => 'Zahlung ausstehend — Admin',
                'description' => 'Wird für den Admin in die Warteschlange gestellt, wenn eine Zahlung aussteht oder eine Aktion erforderlich ist.',
                'default_subject' => 'Zahlung für Buchung #{booking_id} ausstehend',
                'default_body' => "Eine Zahlung steht aus.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nZahlung: {payment_status_label}\n\nPreisübersicht:\n{price_summary}\n\nBuchungsnummer: #{booking_id}",
            ],
            'payment_failed_admin' => [
                'title' => 'Zahlung fehlgeschlagen — Admin',
                'description' => 'Wird für den Admin in die Warteschlange gestellt, wenn eine Zahlung fehlschlägt.',
                'default_subject' => 'Zahlung für Buchung #{booking_id} fehlgeschlagen',
                'default_body' => "Eine Zahlung ist fehlgeschlagen.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nZahlung: {payment_status_label}\nBuchungsnummer: #{booking_id}",
            ],
            'payment_refunded_customer' => [
                'title' => 'Zahlung erstattet — Kunde',
                'description' => 'Wird für den Kunden in die Warteschlange gestellt, wenn eine Zahlung erstattet wird.',
                'default_subject' => 'Ihre Zahlung wurde erstattet',
                'default_body' => "Hallo {customer_name},\n\nIhre Zahlung wurde erstattet.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nBuchungsnummer: #{booking_id}",
            ],
            'payment_refunded_admin' => [
                'title' => 'Zahlung erstattet — Admin',
                'description' => 'Wird für den Admin in die Warteschlange gestellt, wenn eine Zahlung erstattet wird.',
                'default_subject' => 'Zahlung für Buchung #{booking_id} erstattet',
                'default_body' => "Eine Zahlung wurde erstattet.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nBuchungsnummer: #{booking_id}",
            ],
            'invoice_created_customer' => [
                'title' => 'Rechnung erstellt — Kunde',
                'description' => 'Wird für den Kunden in die Warteschlange gestellt, wenn eine Rechnung erstellt wird.',
                'default_subject' => 'Rechnung für Buchung #{booking_id}',
                'default_body' => "Hallo {customer_name},\n\nfür Ihre Buchung wurde eine Rechnung erstellt.\n\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\n\nPreisübersicht:\n{price_summary}\n\nBuchungsnummer: #{booking_id}",
            ],
            'invoice_created_admin' => [
                'title' => 'Rechnung erstellt — Admin',
                'description' => 'Wird für den Admin in die Warteschlange gestellt, wenn eine Rechnung erstellt wird.',
                'default_subject' => 'Rechnung für Buchung #{booking_id} erstellt',
                'default_body' => "Eine Rechnung wurde erstellt.\n\nKunde: {customer_name}\nE-Mail: {customer_email}\nLeistung: {package_title}\nDatum: {booking_date}\nUhrzeit: {start_time} - {end_time}\nBuchungsnummer: #{booking_id}",
            ],
            'contact_form_admin' => [
                'title' => 'Kontaktformular — Admin',
                'description' => 'Wird an den Admin gesendet, wenn ein Besucher das Slotera-Kontaktformular absendet.',
                'default_subject' => '[{site_name}] Neue Kontaktanfrage',
                'default_body' => "Neue Nachricht über das Kontaktformular.\n\nName: {contact_name}\nE-Mail: {contact_email}\nTelefon: {contact_phone}\nBetreff: {contact_subject}\nNachricht:\n{contact_message}\n\nSeite: {contact_page_title}\nURL: {contact_page_url}\nGesendet: {contact_submitted_at}\nSprache: {contact_locale}\nIP: {contact_user_ip}\nBrowser / Gerät: {contact_user_agent}",
            ],
            'magic_link_customer' => [
                'title' => 'Magic Link',
                'description' => 'Vorlage für zukünftige Login-E-Mails für Kunden.',
                'default_subject' => 'Ihr Login-Link',
                'default_body' => "Hallo {customer_name},\n\nverwenden Sie diesen Link, um sich in Ihrem Konto anzumelden:\n\n{magic_link}\n\nDieser Link läuft bald ab.",
            ],
            'marketing_promo' => [
                'title' => 'Marketing — Sonderaktion',
                'description' => 'Wiederverwendbare Marketingvorlage für Kampagnen, Angebote und Rückgewinnungs-E-Mails.',
                'default_subject' => '{headline}',
                'default_body' => "Hallo {customer_name},\n\n{headline}\n\n{message}\n\n{submessage}\n\n{coupon_code}\n\n{cta_url}",
                'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Sonderangebot</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Ihr Angebotscode</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · gültig bis {coupon_expires}</p>
  </div>
</div>',
            ],
        ],
        'hr_HR' => array (
  'booking_created_customer' => 
  array (
'title' => 'Rezervacija stvorena — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se rezervacija stvori.',
'default_subject' => 'Primili smo vaš zahtjev za rezervaciju',
'default_body' => 'Pozdrav {customer_name},

Hvala na rezervaciji. Vaša je rezervacija uspješno zaprimljena.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Status rezervacije: {status_label}
Plaćanje: {payment_status_label}

Sažetak cijene:
{price_summary}

Broj rezervacije: #{booking_id}

Otkaži rezervaciju: {cancellation_url}
Promijeni termin rezervacije: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Nova rezervacija — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se stvori nova rezervacija.',
'default_subject' => 'Primljena je nova rezervacija',
'default_body' => 'Primljena je nova rezervacija.

Kupac: {customer_name}
E-pošta: {customer_email}
Telefon: {customer_phone}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Status rezervacije: {status_label}
Plaćanje: {payment_status_label}

Sažetak cijene:
{price_summary}

Broj rezervacije: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Rezervacija potvrđena — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se rezervacija potvrdi.',
'default_subject' => 'Vaša je rezervacija potvrđena',
'default_body' => 'Pozdrav {customer_name},

Vaša je rezervacija potvrđena.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}

Otkaži rezervaciju: {cancellation_url}
Promijeni termin rezervacije: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Rezervacija potvrđena — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se rezervacija potvrdi.',
'default_subject' => 'Rezervacija potvrđena: #{booking_id}',
'default_body' => 'Rezervacija je potvrđena.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Broj rezervacije: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Podsjetnik 24 h — kupac',
'description' => 'Automatski se stavlja u red čekanja 24 sata prije potvrđene rezervacije.',
'default_subject' => 'Podsjetnik: vaša je rezervacija sutra',
'default_body' => 'Pozdrav {customer_name},

Ovo je podsjetnik za vašu nadolazeću rezervaciju.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Otkaži rezervaciju: {cancellation_url}
Promijeni termin rezervacije: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Podsjetnik 2 h — kupac',
'description' => 'Automatski se stavlja u red čekanja 2 sata prije potvrđene rezervacije.',
'default_subject' => 'Podsjetnik: vaša rezervacija uskoro počinje',
'default_body' => 'Pozdrav {customer_name},

Vaša rezervacija uskoro počinje.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Rezervacija otkazana — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se rezervacija otkaže.',
'default_subject' => 'Vaša je rezervacija otkazana',
'default_body' => 'Pozdrav {customer_name},

Vaša je rezervacija otkazana.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Rezervacija otkazana — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se rezervacija otkaže.',
'default_subject' => 'Rezervacija otkazana: #{booking_id}',
'default_body' => 'Rezervacija je otkazana.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Termin rezervacije promijenjen — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se termin rezervacije promijeni.',
'default_subject' => 'Termin vaše rezervacije je promijenjen',
'default_body' => 'Pozdrav {customer_name},

Termin vaše rezervacije je promijenjen.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}

Otkaži rezervaciju: {cancellation_url}
Promijeni termin rezervacije: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Termin rezervacije promijenjen — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se termin rezervacije promijeni.',
'default_subject' => 'Termin rezervacije promijenjen: #{booking_id}',
'default_body' => 'Termin rezervacije je promijenjen.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Status rezervacije: {status_label}
Plaćanje: {payment_status_label}
Broj rezervacije: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Rezervacija dovršena — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se rezervacija označi dovršenom.',
'default_subject' => 'Hvala što ste nas odabrali.',
'default_body' => 'Pozdrav {customer_name},

Hvala što ste nas odabrali. Vaša je rezervacija sada dovršena.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Rezervacija dovršena — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se rezervacija označi dovršenom.',
'default_subject' => 'Rezervacija dovršena: #{booking_id}',
'default_body' => 'Rezervacija je dovršena.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Broj rezervacije: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Usluga promijenjena — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se promijeni usluga ili paket rezervacije.',
'default_subject' => 'Usluga vaše rezervacije je promijenjena',
'default_body' => 'Pozdrav {customer_name},

Usluga za vašu rezervaciju je promijenjena.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Usluga promijenjena — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se promijeni usluga ili paket rezervacije.',
'default_subject' => 'Usluga rezervacije promijenjena: #{booking_id}',
'default_body' => 'Usluga za rezervaciju je promijenjena.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Broj rezervacije: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Plaćanje na čekanju — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada je plaćanje na čekanju ili zahtijeva radnju.',
'default_subject' => 'Plaćanje za vašu rezervaciju je na čekanju',
'default_body' => 'Pozdrav {customer_name},

Plaćanje za vašu rezervaciju je na čekanju.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Plaćanje: {payment_status_label}

Sažetak cijene:
{price_summary}

Broj rezervacije: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Plaćanje na čekanju — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada je plaćanje na čekanju ili zahtijeva radnju.',
'default_subject' => 'Plaćanje na čekanju za rezervaciju #{booking_id}',
'default_body' => 'Plaćanje je na čekanju.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Plaćanje: {payment_status_label}

Sažetak cijene:
{price_summary}

Broj rezervacije: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Potvrda plaćanja — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se plaćanje potvrdi.',
'default_subject' => 'Plaćanje je primljeno',
'default_body' => 'Pozdrav {customer_name},

Primili smo vašu uplatu.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Plaćanje: {payment_status_label}

Sažetak cijene:
{price_summary}

Broj rezervacije: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Potvrda plaćanja — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se plaćanje potvrdi.',
'default_subject' => 'Plaćanje primljeno za rezervaciju #{booking_id}',
'default_body' => 'Plaćanje je primljeno.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Plaćanje: {payment_status_label}

Sažetak cijene:
{price_summary}

Broj rezervacije: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Plaćanje neuspjelo — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada plaćanje ne uspije.',
'default_subject' => 'Plaćanje nije uspjelo',
'default_body' => 'Pozdrav {customer_name},

Vaše plaćanje nije moguće dovršiti.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Plaćanje neuspjelo — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada plaćanje ne uspije.',
'default_subject' => 'Plaćanje nije uspjelo za rezervaciju #{booking_id}',
'default_body' => 'Plaćanje nije uspjelo.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Plaćanje: {payment_status_label}
Broj rezervacije: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Plaćanje vraćeno — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se izvrši povrat plaćanja.',
'default_subject' => 'Vaša uplata je vraćena',
'default_body' => 'Pozdrav {customer_name},

Izvršen je povrat vaše uplate.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Broj rezervacije: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Plaćanje vraćeno — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se izvrši povrat plaćanja.',
'default_subject' => 'Plaćanje vraćeno za rezervaciju #{booking_id}',
'default_body' => 'Plaćanje je vraćeno.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Broj rezervacije: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Račun izrađen — kupac',
'description' => 'Stavlja se u red čekanja za kupca kada se izradi račun.',
'default_subject' => 'Račun za rezervaciju #{booking_id}',
'default_body' => 'Pozdrav {customer_name},

Za vašu je rezervaciju izrađen račun.

Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}

Sažetak cijene:
{price_summary}

Broj rezervacije: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Račun izrađen — administrator',
'description' => 'Stavlja se u red čekanja za administratora kada se izradi račun.',
'default_subject' => 'Račun izrađen za rezervaciju #{booking_id}',
'default_body' => 'Račun je izrađen.

Kupac: {customer_name}
E-pošta: {customer_email}
Usluga: {package_title}
Datum: {booking_date}
Vrijeme: {start_time} - {end_time}
Broj rezervacije: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Čarobna poveznica — kupac',
'description' => 'Predložak za buduće poruke e-pošte za prijavu klijenta.',
'default_subject' => 'Vaša poveznica za prijavu',
'default_body' => 'Pozdrav {customer_name},

Upotrijebite ovu poveznicu za prijavu na svoj račun:

{magic_link}

Ova poveznica uskoro istječe.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Obrazac za kontakt — administrator',
'description' => 'Šalje se administratoru kada posjetitelj pošalje Slotera obrazac za kontakt.',
'default_subject' => '[{site_name}] Nova kontaktna poruka',
'default_body' => 'Nova poruka iz obrasca za kontakt.

Ime: {contact_name}
E-pošta: {contact_email}
Telefon: {contact_phone}
Predmet: {contact_subject}
Poruka:
{contact_message}

Stranica: {contact_page_title}
URL: {contact_page_url}
Poslano: {contact_submitted_at}
Lokalizacija: {contact_locale}
IP: {contact_user_ip}
Korisnički agent: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Marketing — promocija',
'description' => 'Višekratni marketinški predložak za promotivne kampanje, ponude i poruke za povratak kupaca.',
'default_subject' => '{headline}',
'default_body' => 'Pozdrav {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Posebna ponuda</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Vaš kod ponude</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · vrijedi do {coupon_expires}</p>
  </div>
</div>',
  ),
),
        'mt_MT' => array (
  'booking_created_customer' => 
  array (
'title' => 'Prenotazzjoni maħluqa — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta tinħoloq prenotazzjoni.',
'default_subject' => 'Il-prenotazzjoni tiegħek waslet b’suċċess',
'default_body' => 'Bonġu {customer_name},

Grazzi tal-prenotazzjoni tiegħek. Il-prenotazzjoni tiegħek waslet b’suċċess.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Stat: {status_label}
Ħlas: {payment_status_label}

Sommarju tal-prezz:
{price_summary}

Numru tal-prenotazzjoni: #{booking_id}

Ikkanċella l-prenotazzjoni: {cancellation_url}
Erġa’ skeda l-prenotazzjoni: {reschedule_url}',
  ),
  'booking_created_admin' => 
  array (
'title' => 'Prenotazzjoni ġdida — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta tinħoloq prenotazzjoni ġdida.',
'default_subject' => 'Waslet prenotazzjoni ġdida',
'default_body' => 'Waslet prenotazzjoni ġdida.

Klijent: {customer_name}
E-mail: {customer_email}
Telefon: {customer_phone}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Stat: {status_label}
Ħlas: {payment_status_label}

Sommarju tal-prezz:
{price_summary}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'booking_confirmed_customer' => 
  array (
'title' => 'Prenotazzjoni kkonfermata — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta prenotazzjoni tiġi kkonfermata.',
'default_subject' => 'Il-prenotazzjoni tiegħek hija kkonfermata',
'default_body' => 'Bonġu {customer_name},

Il-prenotazzjoni tiegħek hija kkonfermata.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Numru tal-prenotazzjoni: #{booking_id}

Ikkanċella l-prenotazzjoni: {cancellation_url}
Erġa’ skeda l-prenotazzjoni: {reschedule_url}',
  ),
  'booking_confirmed_admin' => 
  array (
'title' => 'Prenotazzjoni kkonfermata — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta prenotazzjoni tiġi kkonfermata.',
'default_subject' => 'Prenotazzjoni kkonfermata: #{booking_id}',
'default_body' => 'Prenotazzjoni ġiet ikkonfermata.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'booking_reminder_24h_customer' => 
  array (
'title' => 'Tfakkira ta’ 24 siegħa — klijent',
'description' => 'Titqiegħed fil-kju awtomatikament 24 siegħa qabel prenotazzjoni kkonfermata.',
'default_subject' => 'Tfakkira: il-prenotazzjoni tiegħek hija għada',
'default_body' => 'Bonġu {customer_name},

Din hija tfakkira għall-prenotazzjoni li jmiss tiegħek.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Ikkanċella l-prenotazzjoni: {cancellation_url}
Erġa’ skeda l-prenotazzjoni: {reschedule_url}',
  ),
  'booking_reminder_2h_customer' => 
  array (
'title' => 'Tfakkira ta’ sagħtejn — klijent',
'description' => 'Titqiegħed fil-kju awtomatikament sagħtejn qabel prenotazzjoni kkonfermata.',
'default_subject' => 'Tfakkira: il-prenotazzjoni tiegħek tibda dalwaqt',
'default_body' => 'Bonġu {customer_name},

Il-prenotazzjoni tiegħek tibda dalwaqt.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}',
  ),
  'booking_cancelled_customer' => 
  array (
'title' => 'Prenotazzjoni kkanċellata — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta prenotazzjoni tiġi kkanċellata.',
'default_subject' => 'Il-prenotazzjoni tiegħek ġiet ikkanċellata',
'default_body' => 'Bonġu {customer_name},

Il-prenotazzjoni tiegħek ġiet ikkanċellata.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'booking_cancelled_admin' => 
  array (
'title' => 'Prenotazzjoni kkanċellata — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta prenotazzjoni tiġi kkanċellata.',
'default_subject' => 'Prenotazzjoni kkanċellata: #{booking_id}',
'default_body' => 'Prenotazzjoni ġiet ikkanċellata.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'booking_rescheduled_customer' => 
  array (
'title' => 'Prenotazzjoni skedata mill-ġdid — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta prenotazzjoni tiġi skedata mill-ġdid.',
'default_subject' => 'Il-prenotazzjoni tiegħek ġiet skedata mill-ġdid',
'default_body' => 'Bonġu {customer_name},

Il-prenotazzjoni tiegħek ġiet skedata mill-ġdid.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Numru tal-prenotazzjoni: #{booking_id}

Ikkanċella l-prenotazzjoni: {cancellation_url}
Erġa’ skeda l-prenotazzjoni: {reschedule_url}',
  ),
  'booking_rescheduled_admin' => 
  array (
'title' => 'Prenotazzjoni skedata mill-ġdid — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta prenotazzjoni tiġi skedata mill-ġdid.',
'default_subject' => 'Prenotazzjoni skedata mill-ġdid: #{booking_id}',
'default_body' => 'Prenotazzjoni ġiet skedata mill-ġdid.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Stat: {status_label}
Ħlas: {payment_status_label}
Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'booking_completed_customer' => 
  array (
'title' => 'Prenotazzjoni kompluta — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta prenotazzjoni tiġi mmarkata bħala kompluta.',
'default_subject' => 'Grazzi taż-żjara tiegħek',
'default_body' => 'Bonġu {customer_name},

Grazzi taż-żjara tiegħek. Il-prenotazzjoni tiegħek issa hija kompluta.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'booking_completed_admin' => 
  array (
'title' => 'Prenotazzjoni kompluta — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta prenotazzjoni tiġi mmarkata bħala kompluta.',
'default_subject' => 'Prenotazzjoni kompluta: #{booking_id}',
'default_body' => 'Prenotazzjoni tlestiet.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'package_changed_customer' => 
  array (
'title' => 'Pakkett mibdul — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta jinbidel is-servizz jew il-pakkett tal-prenotazzjoni.',
'default_subject' => 'Is-servizz tal-prenotazzjoni tiegħek inbidel',
'default_body' => 'Bonġu {customer_name},

Is-servizz tal-prenotazzjoni tiegħek inbidel.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'package_changed_admin' => 
  array (
'title' => 'Pakkett mibdul — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta jinbidel is-servizz jew il-pakkett tal-prenotazzjoni.',
'default_subject' => 'Is-servizz tal-prenotazzjoni nbidel: #{booking_id}',
'default_body' => 'Is-servizz ta’ prenotazzjoni nbidel.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'payment_pending_customer' => 
  array (
'title' => 'Ħlas pendenti — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta l-ħlas ikun pendenti jew jeħtieġ azzjoni.',
'default_subject' => 'Il-ħlas għall-prenotazzjoni tiegħek għadu pendenti',
'default_body' => 'Bonġu {customer_name},

Il-ħlas tal-prenotazzjoni tiegħek għadu pendenti.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Ħlas: {payment_status_label}

Sommarju tal-prezz:
{price_summary}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'payment_pending_admin' => 
  array (
'title' => 'Ħlas pendenti — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta l-ħlas ikun pendenti jew jeħtieġ azzjoni.',
'default_subject' => 'Ħlas pendenti għall-prenotazzjoni #{booking_id}',
'default_body' => 'Il-ħlas għadu pendenti.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Ħlas: {payment_status_label}

Sommarju tal-prezz:
{price_summary}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'payment_received_customer' => 
  array (
'title' => 'Konferma tal-ħlas — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta l-ħlas jiġi kkonfermat.',
'default_subject' => 'Il-ħlas wasal',
'default_body' => 'Bonġu {customer_name},

Irċevejna l-ħlas tiegħek.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Ħlas: {payment_status_label}

Sommarju tal-prezz:
{price_summary}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'payment_received_admin' => 
  array (
'title' => 'Konferma tal-ħlas — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta l-ħlas jiġi kkonfermat.',
'default_subject' => 'Wasal il-ħlas għall-prenotazzjoni #{booking_id}',
'default_body' => 'Il-ħlas wasal.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Ħlas: {payment_status_label}

Sommarju tal-prezz:
{price_summary}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'payment_failed_customer' => 
  array (
'title' => 'Il-ħlas falla — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta l-ħlas ifalli.',
'default_subject' => 'Il-ħlas falla',
'default_body' => 'Bonġu {customer_name},

Il-ħlas tiegħek ma setax jitlesta.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'payment_failed_admin' => 
  array (
'title' => 'Il-ħlas falla — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta l-ħlas ifalli.',
'default_subject' => 'Il-ħlas falla għall-prenotazzjoni #{booking_id}',
'default_body' => 'Il-ħlas falla.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Ħlas: {payment_status_label}
Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'payment_refunded_customer' => 
  array (
'title' => 'Ħlas rifuż — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta l-ħlas jiġi rifuż.',
'default_subject' => 'Il-ħlas tiegħek ġie rifuż',
'default_body' => 'Bonġu {customer_name},

Il-ħlas tiegħek ġie rifuż.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'payment_refunded_admin' => 
  array (
'title' => 'Ħlas rifuż — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta l-ħlas jiġi rifuż.',
'default_subject' => 'Il-ħlas ġie rifuż għall-prenotazzjoni #{booking_id}',
'default_body' => 'Il-ħlas ġie rifuż.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'invoice_created_customer' => 
  array (
'title' => 'Fattura maħluqa — klijent',
'description' => 'Titqiegħed fil-kju għall-klijent meta tinħoloq fattura.',
'default_subject' => 'Fattura għall-prenotazzjoni #{booking_id}',
'default_body' => 'Bonġu {customer_name},

Inħolqot fattura għall-prenotazzjoni tiegħek.

Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}

Sommarju tal-prezz:
{price_summary}

Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'invoice_created_admin' => 
  array (
'title' => 'Fattura maħluqa — amministratur',
'description' => 'Titqiegħed fil-kju għall-amministratur meta tinħoloq fattura.',
'default_subject' => 'Inħolqot fattura għall-prenotazzjoni #{booking_id}',
'default_body' => 'Inħolqot fattura.

Klijent: {customer_name}
E-mail: {customer_email}
Servizz: {package_title}
Data: {booking_date}
Ħin: {start_time} - {end_time}
Numru tal-prenotazzjoni: #{booking_id}',
  ),
  'magic_link_customer' => 
  array (
'title' => 'Link maġiku — klijent',
'description' => 'Mudell għal emails futuri ta’ dħul tal-klijenti.',
'default_subject' => 'Il-link tad-dħul tiegħek',
'default_body' => 'Bonġu {customer_name},

Uża din il-link biex tidħol fil-kont tiegħek:

{magic_link}

Din il-link tiskadi dalwaqt.',
  ),
  'contact_form_admin' => 
  array (
'title' => 'Formola ta’ kuntatt — amministratur',
'description' => 'Tintbagħat lill-amministratur meta viżitatur jibgħat il-formola ta’ kuntatt ta’ Slotera.',
'default_subject' => '[{site_name}] Messaġġ ġdid ta’ kuntatt',
'default_body' => 'Messaġġ ġdid mill-formola ta’ kuntatt.

Isem: {contact_name}
E-mail: {contact_email}
Telefon: {contact_phone}
Suġġett: {contact_subject}
Messaġġ:
{contact_message}

Paġna: {contact_page_title}
URL: {contact_page_url}
Mibgħut: {contact_submitted_at}
Lingwa: {contact_locale}
IP: {contact_user_ip}
User agent: {contact_user_agent}',
  ),
  'marketing_promo' => 
  array (
'title' => 'Marketing — promozzjoni',
'description' => 'Mudell ta’ marketing li jista’ jerġa’ jintuża għal kampanji promozzjonali, offerti u emails ta’ ritorn.',
'default_subject' => '{headline}',
'default_body' => 'Bonġu {customer_name},

{headline}

{message}

{submessage}

{coupon_code}

{cta_url}',
'default_html_body' => '<div style="text-align:center;">
  <p style="margin:0 0 10px;color:{theme_muted_text_color};font-size:13px;font-weight:700;text-transform:uppercase;letter-spacing:.08em;">Offerta speċjali</p>
  <h1 style="margin:0 0 14px;color:{theme_text_color};font-size:30px;line-height:1.2;">{headline}</h1>
  <p style="margin:0 auto 16px;max-width:520px;color:{theme_text_color};font-size:16px;line-height:1.7;">{message}</p>
  <p style="margin:0 auto 22px;max-width:520px;color:{theme_muted_text_color};font-size:14px;line-height:1.6;">{submessage}</p>
  {cta_button}
  <div style="margin:26px auto 0;max-width:420px;padding:16px;border:1px dashed {theme_primary_color};border-radius:16px;background:{theme_card_background_color};">
<p style="margin:0 0 6px;color:{theme_muted_text_color};font-size:13px;">Il-kodiċi tal-offerta tiegħek</p>
<p style="margin:0;color:{theme_primary_color};font-size:24px;font-weight:800;letter-spacing:.04em;">{coupon_code}</p>
<p style="margin:8px 0 0;color:{theme_muted_text_color};font-size:12px;">{coupon_discount} · valid until {coupon_expires}</p>
  </div>
</div>',
  ),
),
    ];

    return $cache;
    }
}
