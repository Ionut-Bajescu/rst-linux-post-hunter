<?php  
# RST

include 'simple_html_dom.php';

define('DATA_FILE', 'rst.txt');

$categorii_permise = ['Anunturi importante si regulile forumului', 'Bine ai venit (aici te poti prezenta)', 'RST Power', 'Exploituri si POCs', 'Competitie [challenges]', 'Bug Bounty', 'Club ShowOff', 'Games Hacks', 'Tutoriale', 'Tutoriale in romana', 'Tutoriale in engleza', 'Tutoriale video', 'Programare', 'Web Development', 'Mobile phones', 'Sisteme de Operare | Discutii Hardware', 'Electronica', 'Wireless Pentesting', 'Black SEO & Monetizare', 'Programe de afiliere', 'Programe WHS SI BHS', 'Articole', 'Programe Hack', 'Programe Securitate', 'Stuff tools', 'RST Market', 'Bloguri si bloggeri', 'Off-topic', 'Stiri Securitate', 'Ajutor', 'CERERI [numai aici]', 'Invitatii trackere', 'Sugestii', 'Linkuri'];
$auto_open_cats = [];
$ignore_users = [];

$sleep = 60;
for ($i=1; $i < count($argv); $i++){
	switch($argv[$i]){
		case '--sleep':
		case '-s':
			// Sleep time in seconds
			$sleep = $argv[$i+1];
			$i++;
			break;

		case '--categorii':
		case '-c':
			$categorii_permise = explode('|', $argv[$i+1]);
			$i++;
			break;

		case '--ignore_users':
		case '-i':
			$ignore_users = explode('|', $argv[$i+1]);
			$i++;
			break;

		case '--auto_open_cats':
		case '-a':
			$auto_open_cats = explode('|', $argv[$i+1]);
			$i++;
			break;
		
		default:
			exit('Argumentul '.$argv[$i].' este necunoscut!'.PHP_EOL);
			break;
	}
}

$prima = true;
while(true){
	$alerta_posturi = array(); // resetam alertele
	$html = file_get_html('http://rstforums.com');
	$posturi = $html->find('a.postTitle');

	$posturi_vechi = explode(PHP_EOL, @file_get_contents(DATA_FILE));
	file_put_contents(DATA_FILE, ''); // clear file

	$max_for = 10;
	for($i=0; $i < $max_for; $i++){
		preg_match('/([0-9]*)$/', $posturi[$i]->href, $match_post_id);	
		$posturi[$i]->plaintext = $match_post_id[1];
		$post_id = $match_post_id[1];
		if( $posturi[$i]->plaintext != $posturi_vechi[$i] ){

			preg_match('/in (.*?)$/', $posturi[$i]->parent()->plaintext, $match);
			$cat = trim($match[1]);

			if( in_array($cat, $categorii_permise) Or $categorii_permise === false ){
				// In caz ca postul este intr-o categorie care ne place SAU acceptam toate categoriile

				$user = trim($posturi[$i]->parent()->find('span.author')[0]->plaintext);
				if( !in_array($user, $ignore_users) ){
					// In caz ca userul nu este adaugat la ignore users
					$alerta_posturi[] = $posturi[$i]->parent()->plaintext;
					if(in_array($cat, $auto_open_cats)){
						$posturi[$i]->href = htmlspecialchars_decode( $posturi[$i]->href );
						exec("gnome-open '{$posturi[$i]->href}'");
					}
				}
			}


			# Schimbam ordinea posturilor 
			$last = $posturi_vechi[$i];
			for($n=$i+1; $n < $max_for; $n++){ 
				$last_temp = @$posturi_vechi[$n];
				$posturi_vechi[$n] = $last;
				$last = $last_temp;
			}
		} 
		file_put_contents(DATA_FILE, $posturi[$i]->plaintext.PHP_EOL, FILE_APPEND);
	}
	if(count($alerta_posturi) > 0 && $prima === false){
		# In caz ca sunt posturi noi de afisat vom afisa o notificare cu ele
		exec('notify-send "Notificare RST" "'.implode($alerta_posturi, "\n --------------------------------- \n").'"'); 		
	}

	$prima = false;
	sleep($sleep);
}
?>