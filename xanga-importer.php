<?php
/*
Plugin Name: Xanga Importer by PopeOnABomb
Plugin URI: http://www.robotfloss.com
Description: Import your Xanga posts from a Xanga Archive
Version: 3.1
Author: Pope
Author URI: http://www.robotfloss.com
*/

// Last updated by Pope of www.robotfloss.com) on Saturday, March 19, 2010.
// See line 102.

// Xanga archive importer by Jeremy Jay
// Borrows heavily from the LiveJournal import script.
//
// Modified for current WP (3.1) release and for current Xanga archive style by Daniel Kozlowski
//
// ATTN:  unhtmlentities() has been disabled due to it's turning all ". " into ".? " 
//        once the post was imported into wordpress 2.1.  Thus, it has been disabled.  If 
//        you're using a version of PHP earlier than 4.3, you'll need to un-comment
//        calls to unhtmlentities() in this script.  They are on lines 82 and 120.
//
// ATTN:  I've run into some Xanga archives where the timestamp for a post or comment is 
//	      missing a zero.  For example, "12:08" is printed as "12:8".  This script 
//	      cannot read those!  Please read through your code if some of the times are
//      

if (!defined('WP_LOAD_IMPORTERS')) return;

// Load Importer API
require_once ABSPATH . 'wp-admin/includes/import.php';

if (!class_exists('WP_Importer')) {
	$class_wp_importer = ABSPATH . 'wp-admin/includes/class-wp-importer.php';
	if (file_exists($class_wp_importer)) require_once $class_wp_importer;
}

if (class_exists('WP_Importer')) {
	class Xanga_Import extends WP_Importer {
 
  	var $file;

		function header() {
			echo '<div class="wrap">';
			echo '<h2>' . __( 'Import Xanga' , 'xanga-importer') . '</h2>';
		}

    function footer() {
        echo '</div>';
    }
        
    function greet() {
        echo '<p>'.__('Howdy! This importer allows you to extract posts and comments from Xanga Premium Archive files into your blog.  If you do not have Premium but have enough posts to be looking at this, just pay $4 for a month to get the archive and you will at least be supporting Xanga for all the hosting they have done for you.  Pick an archive file to upload and click Import.').'</p><br />';
        wp_import_upload_form("admin.php?import=xanga&amp;step=1");
    }

    function unhtmlentities($string) { // From php.net for < 4.3 compatability
        $trans_tbl = get_html_translation_table(HTML_ENTITIES);
        $trans_tbl = array_flip($trans_tbl);
        return strtr($string, $trans_tbl);
    }
    
    function import_posts() {
        global $wpdb, $current_user;
        
        set_magic_quotes_runtime(0);
        $importdata = file($this->file); // Read the file into an array
        $importdata = implode('', $importdata); // squish it
        $importdata = str_replace(array ("\r\n", "\r"), "\n", $importdata);
        
        preg_match_all('|<div class="blogheader">(.*?)<hr size=1 noshade>(<div class="blogheader">)*?|is', $importdata, $posts);
        $posts = $posts[1];
        unset($importdata);
        echo '<ol>';        
				
				// Counter
				$countPosts = 0;
				$countComments = 0;
        
        foreach ($posts as $post) {
        	      	
            flush();
            preg_match('|^(.*?)</div>|is', $post, $post_title);
            $post_title = $wpdb->escape(trim($post_title[1]));
            
            preg_match('/<div class="smalltext">Posted (.*?)\/(.*?)\/(.*?) at (.*?) (.*?)<\/div>/is', $post, $match);
            list($hour,$min) = explode(':',$match[4]);         
            switch($match[5]) { 
            	case 'AM' : if($hour == 12) $hour = 0; break; 
            	case 'PM': if($hour < 12) $hour += 12; break; 
            	}
            $match[4] = "$hour:$min";
            $post_date = "$match[3]-$match[1]-$match[2] $match[4]:00";
            echo "Date: $post_date";

            $com = split(' ', $post_title);
            
            if( $com[1]=='Comments' ) {
                preg_match_all('|<div class="ctextfooterwrap"><div class="ctext">(.*?)</div></div>|is', $post, $comments);
                $comments = $comments[1];

                $comment_post_ID = $post_id;
                $num_comments = 0;
                foreach ($comments as $comment) {                		
                    preg_match('|^(.*?)</div><div class="cfooter">|is', $comment, $comment_content);
                    $comment_content = str_replace(array ('<![CDATA[', ']]>'), '', trim($comment_content[1]));
                    //$comment_content = $this->unhtmlentities($comment_content);

                    // Clean up content
                    $comment_content = preg_replace('|<(/?[A-Z]+)|e', "'<' . strtolower('$1')", $comment_content);
                    $comment_content = str_replace('<br>', '<br />', $comment_content);
                    $comment_content = str_replace('<hr>', '<hr />', $comment_content);
                    $comment_content = $wpdb->escape($comment_content);
                    
                    preg_match('/<div class="cfooter">Posted (.*?)\/(.*?)\/(.*?) at (.*?) (.*?) by/i', $comment, $match);
                    list($hour,$min) = explode(':',$match[4]);         
		            		switch($match[5]) { 
		            			case 'AM' : if($hour == 12) $hour = 0; break; 
		            			case 'PM': if($hour < 12) $hour += 12; break; 
		            		}
          	  			$match[4] = "$hour:$min";
            	  		$comment_date = "$match[3]-$match[1]-$match[2] $match[4]:00";

										// Somewhere in mid June of 2005, Xanga changed the way that comments linked to the author's URL.
										// Before June 24th, 2005 the format was: http://www.xanga.com/home.aspx?user=the_users_name
										// After June 24th, 2005 the format is: http://www.xanga.com/the_users_name
			
										// Get the author's name from the URL format previous to June 24th, 2005.
										preg_match('|<a href="http://www\.xanga\.com/home\.aspx\?user=(.*?)">(.*?)</a>|i', $comment, $comment_author);
										$comment_author = $wpdb->escape(trim($comment_author[1]));
					
										// If the post is from after June 24th, 2005, the author's name will be anonymous.
										// If the comment is anonymous, then try to get the author's name again - but using the URL format of posts after June 24th, 2005.
										// If the author is still anonymous, then we'll presume the comment is actually anonymous.
										if (($comment_author == "Anonymous") || ($comment_author == "")) {
											preg_match('|<a href="http://www\.xanga\.com/(.*?)">(.*?)</a>|i', $comment, $comment_author);
											$comment_author = $wpdb->escape(trim($comment_author[1]));
											
											// If the author is still anonymous, then try with the new sub-domain format of http://username.xanga.com/
											if (($comment_author == "Anonymous") || ($comment_author == "")) {
												preg_match('|<a href="http://(.*?)\.xanga\.com/">(.*?)</a>|i', $comment, $comment_author);
												$comment_author = $wpdb->escape(trim($comment_author[1]));
											}							
										}
			
										$comment_author_url = "http://" . $comment_author . ".xanga.com/";
                    					
                    $comment_approved = 1;
                    // Check if it's already there
                    if (!comment_exists($comment_author, $comment_date)) {
                        $commentdata = compact('comment_post_ID', 'comment_author', 'comment_author_url', 'comment_date', 'comment_content', 'comment_approved');
                        $commentdata = wp_filter_comment($commentdata);
                        wp_insert_comment($commentdata);
                        $num_comments++;
                				// Increase the tally of imported comments
                				$countComments++;                        
                    }
                }
                if ( $num_comments ) {
                    echo ' ';
                    printf(__('(%s comments)'), $num_comments);
                }
            } else {

                preg_match('|<td style="padding-left:20; padding-bottom:20">(.*?)<div class="smalltext">Posted (\d{1,2}/\d{1,2}/\d{4}) at (.*?)<\/div>|is', $post, $post_content);
                $post_content = str_replace(array ('<![CDATA[', ']]>'), '', trim($post_content[1]));
                //$post_content = $this->unhtmlentities($post_content);

                // Clean up content
                $post_content = preg_replace('|<(/?[A-Z]+)|e', "'<' . strtolower('$1')", $post_content);
                $post_content = str_replace('<br>', '<br />', $post_content);
                $post_content = str_replace('<hr>', '<hr />', $post_content);
                
                //Xanga archives have some pretty crappy formatting, so this reduces the string to a single line.
                //THIS WILL NOT REMOVE YOUR OWN FORMATTING.  Any formatting changes you created in your posts
                //are tagged, and thus will not be affected by the removal.
                $post_content = str_replace("\n", " ", $post_content);
                $post_content = $wpdb->escape($post_content);

                $post_author = $current_user->ID;
                $post_status = 'publish';

                echo '<li>';
                if ($post_id = post_exists($post_title, $post_content, $post_date)) {
                    printf(__('Post <i>%s</i> already exists.'), stripslashes($post_title));
                } else {
                    printf(__('Importing post <i>%s</i>...'), stripslashes($post_title));
                    $postdata = compact('post_author', 'post_date', 'post_content', 'post_title', 'post_status');
                    $post_id = wp_insert_post($postdata);
				        		// Increase the tally of imported posts
										$countPosts++;
                    
                    if (!$post_id) {
                        _e("Couldn't get post ID");
                        echo '</li>';
                        break;
                    }
                }
            }

            echo '</li>';
            flush();
            ob_flush();
        }
        echo '</ol>';
        
        echo "<p>Imported {$countPosts} posts and {$countComments} comments.";
    }

    function import() {
        echo '<h3>';
        echo 'Starting Import';
        echo '</h3>';
        
        $file = wp_import_handle_upload();
        if ( isset($file['error']) ) {
            echo $file['error'];
            return;
        }

        $this->file = $file['file'];
        $this->import_posts();
        wp_import_cleanup($file['id']);
        
        echo '<h3>';
        printf(__('All done. <a href="%s">Have fun!</a>'), get_option('home'));
        echo '</h3>';
    }

    function start() {
        if (empty ($_GET['step']))
            $step = 0;
        else
            $step = (int) $_GET['step'];

        $this->header();
        
        switch ($step) {
            case 0 :
                $this->greet();
                break;
            case 1 :
                $this->import();
                break;
        }
        $this->footer();
    }
	}
}

$xanga_import = new Xanga_Import();

register_importer('xanga', __('Xanga', 'xanga-importer'), __('Import posts and comments from a Xanga archive.', 'xanga-importer'), array ($xanga_import, 'start'));

function xanga_importer_init() {
    load_plugin_textdomain( 'xanga-importer', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
}

add_action( 'init', 'xanga_importer_init' );
