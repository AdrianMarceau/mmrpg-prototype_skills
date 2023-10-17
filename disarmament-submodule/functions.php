<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-robot_check-skills_end-of-turn' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Define a flag to hold whether or not we removed anything
        $explosive_objects_removed = 0;

        // Define the list of explosive attachments/hazards that this skill removes
        $explosive_object_tokens = array(
            'remote-mine_remote-mine',  // battle_attachments/side-position/ability_remote-mine_remote-mine_side-position
            'galaxy-bomb_black-hole',  // battle_attachments/side-position/ability_galaxy-bomb_black-hole_side-position
            'crash-bomber_crash-bomb',  // robot_attachments//ability_crash-bomber_crash-bomb
            'chain-blast_chain-bomb',  // robot_attachments//ability_chain-blast_chain-bomb
                'chain-blast_fx',  // robot_attachments//ability_chain-blast_fx
            );

        // Loop through field attachments on this side of the battle first
        if (!empty($this_battle->battle_attachments)){
            foreach ($this_battle->battle_attachments AS $side_position => $battle_attachments){
                if (!strstr($side_position, $this_player->player_side.'-')){ continue; }
                //error_log('$battle_attachments = '.print_r($battle_attachments, true));
                foreach ($battle_attachments AS $attachment_token => $attachment_info){
                    $attachment_token_clean = preg_replace('/^ability_([-_a-z0-9]+)_(?:[-a-z0-9]+)$/', '$1', $attachment_token);
                    if (!in_array($attachment_token_clean, $explosive_object_tokens)){ continue; }
                    $explosive_objects_removed++;
                    $this_battle->unset_attachment($side_position, $attachment_token);
                }
            }
        }

        // Collect a list of robots from this player and then loop through 'em
        $this_player_robots = $this_player->values['robots_active'];
        if (!empty($this_player_robots)){
            foreach ($this_player_robots AS $robot_key => $robot_info){
                if ($robot_info['robot_id'] === $this_robot->robot_id){ $this_player_robot = $this_robot; }
                else { $this_player_robot = rpg_game::get_robot($this_battle, $this_player, array('robot_id' => $robot_info['robot_id'])); }
                if (empty($this_player_robot)){ unset($this_player_robot); continue; }
                // Loop through this robot's attachments
                if (!empty($this_player_robot->robot_attachments)){
                    foreach ($this_player_robot->robot_attachments AS $attachment_token => $attachment_info){
                        $attachment_token_clean = preg_replace('/^ability_([-_a-z0-9]+)$/', '$1', $attachment_token);
                        if (!in_array($attachment_token_clean, $explosive_object_tokens)){ continue; }
                        $explosive_objects_removed++;
                        $this_player_robot->unset_attachment($attachment_token);
                    }
                }
                unset($this_player_robot);
            }
        }

        // Check to see if any explosives were removed this way
        if (!empty($explosive_objects_removed)){

            // Print a message showing that this effect is taking place
            $this_robot->set_frame('taunt');
            $this_battle->queue_sound_effect('debuff-received');
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                $this_robot->print_name().' removed explosive hazards from '.$this_robot->get_pronoun('possessive2').' side of the field!',
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

        }

        // Return true on success
        return true;

    }
);
?>
