<!doctype html>
<html lang="pl">
<head>
   <meta charset="utf-8">
   <title>ZOOM-links-update</title>
   <meta name="Author" content="Gorka Mateusz">
   <meta name="robots" content="noindex,nofollow">
   <meta name="viewport" content="width=device-width, initial-scale=1">
   <link rel="Short icon" href=""/>
   <link rel="Stylesheet" type="text/css" href="css/main.css"/>
</head>

<?php
	/**
	 * 1. Próbuje zalogować się do skrzynki mailowej
	 */

	// Dołącza dane konfiguracyjne
	include("config.php");

	/// Próbuje połączyć się ze skrzynką
	$imapResource = imap_open( MAIL_MAILBOX, MAIL_ADDRESS, MAIL_PASSWORD );

	/// Jeśli błąd wyrzuca wyjątek z błędem połączenia.
	if( $imapResource === false )
		throw new Exception( imap_last_error() );

?>

<?php
	/**
	 * 2. Filtruje, czyta wiadomości
	 */

	/// Filtr przeszukiwania wiadomości, rozpatruje tylko wiadomości z ostatniego tygodnia
	$search = 'SINCE "' . date("j F Y", strtotime("-". LAST_DAYS ." days")) . '"';

	/// Ładuje przefiltrowane wiadmości
	$emails = imap_search( $imapResource, $search );

	///- Czyta wiadomości i próbuje stworzyć zaproszenia
	include("class/Invitation.php");

	$cnt_correct = 0; 		///< Licznik poprawnych
	$cnt_saved = 0;			///< Licznik zapisanych

	if( ! empty( $emails ) ){

		// Komunikat o ilości przetowrzonych wiadomości
		echo "Przeczytano " . count( $emails ) . " wiadomości.<br/>";

		foreach( $emails as $email ){

			// Pobiera nagłówek wiadomosci
			$overview = imap_fetch_overview( $imapResource, $email );
			$overview = $overview[0];

			// Filtr nagłówka
			if( preg_match( FILTR_ADRESAT, $overview->from ) == 0 )
				continue;

			// Przetwarza treść wiadomości
			$message = imap_fetchbody( $imapResource, $email, 1 );
			$message = quoted_printable_decode( $message );

			// Próbuje stworzyć zaproszenie
			$invitation = new Invitation( $message );


			if( $invitation->isOK() ){
				///- inkrementuje licznik poprawnych
				$cnt_correct++;

				/**
				 * 3. Zapisuje wiadomości do pliku danych
				 */
				if( $invitation->save() ){
					///- inkrementuje licznik zapisanych
					$cnt_saved++;

					///- Wyświetla zapisaną wiadomość
					echo "Zapisano nowe spotkanie do pliku.<br/>";
					$invitation->display();
				}

				/**
				 * 4. Usuwa wiadomość ze skrzynki (zależnie od konfiguracji)
				 */
				if( REMOVE_MAIL )
					imap_delete( $imapResource, $email );
			}
		}
	}
	///- Komunikat o pustej skrzynce
	else
		echo "Pusta skrzynka.<br/>";

	///- Komunikat o zapisanych i poprawnych zaproszeniach
	echo "Zapisano $cnt_saved z $cnt_correct porawnych zaproszeń.</>";

	/// 5. Zamyka skrzynkę
	imap_expunge( $imapResource );
	imap_close( $imapResource );

?>

</html>