<?php
/*
  Plugin Name: Github Wordpress Widget
  Plugin URI: http://www.pgogy.com/code/groups/wordpress/github-wordpress-widget/
  Description: A widget for displaying github profiles
  Version: v.1.chagrined.0.0.aromatic..7
  Author: Pgogy
  Author URI: http://www.pgogy.com
  License: GPL2
  */

class githubwordpress extends WP_Widget {
	function githubwordpress() {
		
		require(dirname(__FILE__) . "/languages/" . get_bloginfo('language') . "/index.php");
	
        $options = array('classname' => 'githubwordpress', 'description' => __($github_description));
		$this->WP_Widget('githubwordpress', __($github_name), $options);
	}
	
	function form($instance) {
		
        if (file_exists(dirname(__FILE__) . "/languages/" . get_bloginfo('language') . "/index.php")) {
		
			require(dirname(__FILE__) . "/languages/" . get_bloginfo('language') . "/index.php");
        } else {
		
			require(dirname(__FILE__) . "/languages/en-US/index.php");
		}
		
		echo '<div id="githubwordpress-widget-form">';
        echo '<p><label for="' . $this->get_field_id("username") . '">' . $github_username . ' :</label>';
		echo '<input type="text" name="' . $this->get_field_name("username") . '" ';
		echo 'id="' . $this->get_field_id("username") . '" value="' . $instance["username"] . '" /></p>';
        echo '<p><label for="' . $this->get_field_id("username") . '">' . $github_password . ' :</label>';
		echo '<input type="password" name="' . $this->get_field_name("password") . '" ';
		echo 'id="' . $this->get_field_id("password") . '" value="' . $instance["password"] . '" /></p>';
		echo "<p>" . $github_warning . "</p>";
		echo '<p><label for="' . $this->get_field_id("hidden") . '">' . $github_repo . ':</label>';
		echo '<select id="' . $this->get_field_id("hidden") . '" name="' . $this->get_field_name("hidden") . '">';

		if ($instance['hidden'] == "0") {
			echo '<option value="0" selected="selected">' . $github_no . '</option>';
			echo '<option value="1">' . $github_yes . '</option>';
		} else {
			echo '<option value="0">' . $github_no . '</option>';
			echo '<option value="1" selected="selected">' . $github_yes . '</option>';
		}

		echo '</select>';
		echo '</div>';
	}

	function widget($args, $instance) {
	
		require(dirname(__FILE__) . "/languages/" . get_bloginfo('language') . "/index.php");
	
        if (isset($instance['error']) && $instance['error'])
			return;

        if (isset($args['before_title']))
			$before_title = $args['before_title'];
		else
			$before_title = '<h3 class="widget-title">';
		
        if (isset($args['after_title']))
			$after_title = $args['after_title'];
		else
			$after_title = '</h3>';
		
        if (isset($args['before_widget']))
			$before_widget = $args['before_widget'];
		else
			$before_widget = '';
		
        if (isset($args['after_widget']))
			$after_widget = $args['after_widget'];
		else
			$after_widget = '';
		
		$user = $instance['username'];
		$password = $instance['password'];
		
		if (!empty($password)) {
			$headers = array(
                "Authorization: Basic " . base64_encode($user . ":" . $password)
			);
		}
		
		$url = "https://api.github.com/users/" . $user . "/repos";
		
		$ch = curl_init();
		$vers = curl_version();
		
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_URL => $url,
            CURLOPT_USERAGENT => 'curl/' . $vers['version'])
        );
		
        if (!empty($password)) {
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
		}
		
        if ($data = get_from_database($url)) {
		
        } else {
		$data = curl_exec($ch);
            if ($data && !isset($json->message)) {
                $result = put_to_database($url, $data);
            }
        }
		$json = json_decode($data);
		
		echo $before_widget;
		echo $before_title;
        ?>Latest Commits
			<!-- octocat picture -->
        <div class="github_wordpress_image_holder"><img src="<?php echo plugins_url('/octocat_small.png', __FILE__); ?>" /></div>
        <a target="_blank" href="https://www.github.com/<?php echo $user; ?>"><?php echo $user; ?></a> @ <a target="_blank" href="https://www.github.com">Github</a>
        <p>
        <?php
        if ($instance['hidden']) {
            echo '</p>';
            echo '<div id="githublistdiv" style="display:none"><ul id="githublist">';
					} else {
            echo '</p>';
				echo '<div id="githublistdiv"><ul id="githublist">';
			}
			
        foreach ($json as $repo) {
				if (isset($json->message)) {
                                        echo $github_error . " " . $json->message;
                                        break;
                                }

            echo '<li><a class="gitreponame" target="_blank" href="http://www.github.com/' . $user . '/' . $repo->name . '">' . $repo->name . '</a>';

				$url = "https://api.github.com/repos/" . $user . "/" . $repo->name . "/commits";
            if ($repo_data = get_from_database($url)) {
                
            } else {
				curl_setopt($ch, CURLOPT_URL, $url);
				$repo_data = curl_exec($ch);
                $result = put_to_database($url, $repo_data);
            }
				$repo = json_decode($repo_data);
				$total = 0;
				$counter = 0;

            if (is_array($repo))
                foreach ($repo as $coder) {
                    if ($total == 0) {
                        echo '<p class="git-commiter">'
                        . '<a class="committername" href="' . $coder->committer->url . '" target="_blank">'
                        . $coder->commit->author->name . '</a>'
                        . '<span class="commitdate">' . $coder->commit->author->date . '</span>'
                        . '<span class="commitmessage">' . $coder->commit->message . '</span>'
                        . "</p>";
                    }
					$total++;
                    if ($coder->committer->login == $user)
						$counter++;
				}

            if ($counter == 0) {
					echo  "0 " . $github_percent_string . "</li>";
            } else {
					echo (int) (($counter / $total) * 100) . " " . $github_percent_string . "</li>";
				}
				unset($coder);
			}
		
		curl_close($ch);
		echo "</ul></div>";
		echo $after_widget;
	}
	
	function update($new_instance, $old_instance) {
		$instance = $old_instance;		
		$instance['username'] = strip_tags($new_instance['username']);
		$instance['password'] = strip_tags($new_instance['password']);
		$instance['hidden'] = strip_tags($new_instance['hidden']);
		return $instance;
	}		
	
}

add_action('widgets_init', create_function('', 'return register_widget("githubwordpress");'));
add_action("wp_head", "github_add_scripts");
	
function github_add_scripts() {
    echo '<link rel="stylesheet" href="' . plugins_url("/css/github_wordpress_widget.css", __FILE__) . '" />';
    //echo '<script src="http://code.jquery.com/jquery-latest.js"></script>';
}

/**
 * GET JSON data
 * @param string $url
 */
function get_from_database($url) {
    global $wpdb;
    $timestamp = time() - 7200; // 2 hours old is ok
    $query = "SELECT * FROM git_history_widget WHERE url = " . $wpdb->prepare('%s', $url) . " AND TIMESTAMP > " . (int) $timestamp;
    $myrows = $wpdb->get_results($query);
    if (strlen($myrows[0]->data) > 10)
        return $myrows[0]->data;
    else
        return FALSE;
}

/**
 * Save JSON DATA
 * @param string $url
 * @param strin $data
 */
function put_to_database($url, $data) {
    global $wpdb;
    $timestamp = time();
    $data = $wpdb->prepare('%s', $data);
    $url = $wpdb->prepare('%s', $url);
    $query = "REPLACE INTO git_history_widget SET data = " . $data . ", timestamp = " . (int) $timestamp . ", url = " . $url . "";
    $result = $wpdb->query($query);
    return;
}