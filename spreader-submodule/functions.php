<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_trigger-ability_before' => function($objects){
        //error_log('rpg-robot_trigger-ability_before() for '.$objects['this_robot']->robot_string.' vs '.$objects['target_robot']->robot_string);

        // Extract objects into the global scope
        extract($objects);

        // If this robot does not own the activating ability, it's not relevant
        if ($this_ability->robot !== $this_robot){ return false; }

        // If this robot is not active, the skill doesn't activate
        if ($this_robot->robot_position !== 'active'){ return false; }

        // Save a snapshot of the target's attachment tokens, if any, so we can see what's new
        $start_target_attachment_tokens = array();
        if (!empty($target_robot)){
            $start_target_attachments = $target_robot->get_attachments();
            $start_target_attachment_tokens = !empty($start_target_attachments) ? array_keys($start_target_attachments) : array();
            //error_log('$start_target_attachments = '.print_r($start_target_attachments, true));
            //error_log('$start_target_attachment_tokens = '.print_r($start_target_attachment_tokens, true));
        }
        $this_robot->set_value('watching_target_attachment_tokens', $start_target_attachment_tokens);

        // Also save a snapshot of the field attachment tokens, if any, so we can see what's new
        $field_position = $target_robot->player->player_side.'-'.$target_robot->robot_position;
        if ($target_robot->robot_position === 'bench'){ $field_position .= '-'.$target_robot->robot_key; }
        $start_field_attachments = $this_battle->get_attachments();
        $start_field_attachment_tokens = !empty($start_field_attachments[$field_position]) ? array_keys($start_field_attachments[$field_position]) : array();
        //error_log('$start_field_attachments = '.print_r($start_field_attachments, true));
        //error_log('$start_field_attachment_tokens = '.print_r($start_field_attachment_tokens, true));
        $this_robot->set_value('watching_field_attachment_tokens', $start_field_attachment_tokens);

        // Return true on success
        return true;

    },
    'rpg-ability_trigger-damage_after' => function($objects){
        //error_log('rpg-ability_trigger-damage_after() for '.$objects['this_robot']->robot_string.' w/ ability '.$objects['this_ability']->ability_name);

        // Extract objects into the global scope
        extract($objects);

        // If this robot is not the aggressor, the skill doesn't activate
        if ($options->damage_initiator !== $this_robot){ return false; }
        if (empty($options->damage_target)){ return false; }
        $target_robot = $options->damage_target;

        // Collect the snapshot of the target's attachment tokens, if any, so we can see what's new
        $old_target_attachment_tokens = $this_robot->get_value('watching_target_attachment_tokens');
        if (!is_array($old_target_attachment_tokens)){ $old_target_attachment_tokens = array(); }
        $this_robot->unset_value('watching_target_attachment_tokens');
        $new_target_attachments = $target_robot->get_attachments();
        $new_target_attachment_tokens = !empty($new_target_attachments) ? array_keys($new_target_attachments) : array();
        //error_log('$old_target_attachment_tokens = '.print_r($old_target_attachment_tokens, true));
        //error_log('$new_target_attachment_tokens = '.print_r($new_target_attachment_tokens, true));
        // Collect the difference between the old and new attachment tokens
        $target_attachment_tokens_added = array_diff($new_target_attachment_tokens, $old_target_attachment_tokens);
        $target_attachments_added = array();
        if (!empty($target_attachment_tokens_added)){
            foreach ($target_attachment_tokens_added AS $token){
                $target_attachments_added[$token] = $new_target_attachments[$token];
            }
        }
        //error_log('$target_attachment_tokens_added = '.print_r($target_attachment_tokens_added, true));

        // Collect the snapshot of the field attachment tokens, if any, so we can see what's new
        $old_field_attachment_tokens = $this_robot->get_value('watching_field_attachment_tokens');
        if (!is_array($old_field_attachment_tokens)){ $old_field_attachment_tokens = array(); }
        $this_robot->unset_value('watching_field_attachment_tokens');
        $field_position = $target_robot->player->player_side.'-'.$target_robot->robot_position;
        if ($target_robot->robot_position === 'bench'){ $field_position .= '-'.$target_robot->robot_key; }
        $new_field_attachments = $this_battle->get_attachments();
        $new_field_attachment_tokens = !empty($new_field_attachments[$field_position]) ? array_keys($new_field_attachments[$field_position]) : array();
        //error_log('$old_field_attachment_tokens = '.print_r($old_field_attachment_tokens, true));
        //error_log('$new_field_attachment_tokens = '.print_r($new_field_attachment_tokens, true));
        // Collect the difference between the old and new attachment tokens
        $field_attachment_tokens_added = array_diff($new_field_attachment_tokens, $old_field_attachment_tokens);
        //error_log('$field_attachment_tokens_added = '.print_r($field_attachment_tokens_added, true));
        $field_attachments_added = array();
        if (!empty($field_attachment_tokens_added)){
            foreach ($field_attachment_tokens_added AS $token){
                $field_attachments_added[$token] = $new_field_attachments[$field_position][$token];
            }
        }

        // If there aren't any new attachments added, the skill doesn't activate
        if (empty($target_attachment_tokens_added) && empty($field_attachment_tokens_added)){ return false; }

        // Collect a list of active robots from this player so we can loop through 'em
        $target_player_robots = $target_player->values['robots_active'];
        if (count($target_player_robots) < 2){ return false; }

        // Display a message showing this robot's skill is in effect
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
            ucfirst($this_robot->get_pronoun('possessive2')).' field hazard was spread to other positions!',
            array(
                'this_skill' => $this_skill,
                'canvas_show_this_skill_overlay' => false,
                'canvas_show_this_skill_underlay' => true,
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key
                )
            );
        $this_robot->reset_frame();

        // Collect a list of robots from this player and then loop through 'em
        if (!empty($target_player_robots)){
            foreach ($target_player_robots AS $robot_key => $robot_info){
                if ($robot_info['robot_id'] === $target_robot->robot_id){ $robot = $target_robot; }
                else { $robot = rpg_game::get_robot($this_battle, $target_player, array('robot_id' => $robot_info['robot_id'])); }
                if (empty($robot)){ unset($robot); continue; }

                // Define flag to see if attachments added at all
                $attachments_added = false;

                // If there were robot attachments to spread, make sure this robot had 'em
                if (!empty($target_attachments_added)){
                    foreach ($target_attachments_added AS $token => $attachment){
                        if ($robot->has_attachment($token)){ continue; }
                        $robot->set_attachment($token, $attachment);
                        $attachments_added = true;
                    }
                }

                // If there were field attachments to spread, let's do it to all the other active robots
                $field_position = $robot->player->player_side.'-'.$robot->robot_position;
                if ($robot->robot_position === 'bench'){ $field_position .= '-'.$robot->robot_key; }
                if (!empty($field_attachments_added)){
                    foreach ($field_attachments_added AS $token => $attachment){
                        $attachment['attachment_token'] = preg_replace('/_([-a-z0-9]+)$/i', '_'.$field_position, $token);
                        if ($this_battle->has_attachment($field_position, $attachment['attachment_token'])){ continue; }
                        $this_battle->set_attachment($field_position, $attachment['attachment_token'], $attachment);
                        //error_log('set_attachment('.$field_position.', '.$attachment['attachment_token'].', '.print_r($attachment, true).')');
                        $attachments_added = true;
                    }
                }

                // Show the camera looking at this robot first
                if ($attachments_added){
                    $this_robot->set_frame('summon');
                    $robot->set_frame('defend');
                    $this_battle->events_create($this_robot, false, '', '',
                        array(
                            'this_skill' => $this_skill,
                            'canvas_show_this_skill_overlay' => false,
                            'canvas_show_this_skill_underlay' => true,
                            'event_flag_camera_action' => true,
                            'event_flag_camera_side' => $robot->player->player_side,
                            'event_flag_camera_focus' => $robot->robot_position,
                            'event_flag_camera_depth' => $robot->robot_key
                            )
                        );
                    $robot->reset_frame();
                    $this_robot->reset_frame();
                }

                // Unset the robot object to free memory
                unset($robot);

            }
        }

        // Return true on success
        return true;

    }
);
?>
