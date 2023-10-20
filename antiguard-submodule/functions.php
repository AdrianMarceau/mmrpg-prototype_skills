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
            'super-arm_super-block',  // battle_attachments/side-position/ability_remote-mine_remote-mine_side-position
            'core-shield_{type}',  // robot_attachments//ability_core-shield_{type}
            );
        $object_fx_tokens = array(
            );

        // Loop through the various elemental types and add core shields for each
        $types = rpg_type::get_index();
        foreach ($types AS $token => $info){ $explosive_object_tokens[] = 'core-shield_'.$token; }

        // Create an array to hold positions of special interest (hazards to remove)
        $positions_of_interest = array();

        // Collect a list of active robots from the target player so we can loop through 'em
        $target_player_robots = $target_player->values['robots_active'];
        if (!isset($target_robot)){ $target_robot = $target_player->get_active_robot(); }

        // Loop through field attachments on this side of the battle first
        if (!empty($this_battle->battle_attachments)){
            foreach ($this_battle->battle_attachments AS $side_position => $battle_attachments){
                if (!strstr($side_position, $target_player->player_side.'-')){ continue; }
                $attachment_token_regex = '/^ability_([-_a-z0-9]+)_([-a-z0-9]+)$/';
                foreach ($battle_attachments AS $attachment_token => $attachment_info){
                    $attachment_token_clean = preg_replace($attachment_token_regex, '$1', $attachment_token);
                    $attachment_token_context = preg_replace($attachment_token_regex, '$2', $attachment_token);
                    if (!in_array($attachment_token_clean, $explosive_object_tokens)){ continue; }
                    $attachment_fx_token = false;
                    if (isset($object_fx_tokens[$attachment_token_clean])){
                        $attachment_fx_token = str_replace(
                            $attachment_token_clean,
                            $object_fx_tokens[$attachment_token_clean],
                            $attachment_token
                            );
                        }
                    if (strstr($side_position, 'bench-')){ list($side, $position, $key) = explode('-', $attachment_token_context); }
                    else { list($side, $position) = explode('-', $attachment_token_context); $key = 0; }
                    $robot = array_filter($target_player_robots, function($info) use ($side, $position, $key){
                        if ($info['robot_position'] !== $position){ return false; }
                        if ($position === 'bench' && $info['robot_key'] !== $key){ return false; }
                        return true;
                        });
                    if (!empty($robot)){ $robot = rpg_game::get_robot_by_id($robot[0]['robot_id']); }
                    $positions_of_interest[] = array(
                        'kind' => 'battle',
                        'key' => $side_position,
                        'token' => $attachment_token,
                        'fxtoken' => $attachment_fx_token,
                        'player' => $target_player,
                        'robot' => (!empty($robot) ? $robot : $target_robot),
                        );
                }
            }
        }

        // Collect a list of robots from this player and then loop through 'em
        if (!empty($target_player_robots)){
            foreach ($target_player_robots AS $robot_key => $robot_info){
                if ($robot_info['robot_id'] === $target_robot->robot_id){ $robot = $target_robot; }
                else { $robot = rpg_game::get_robot($this_battle, $target_player, array('robot_id' => $robot_info['robot_id'])); }
                if (empty($robot)){ unset($robot); continue; }
                // Loop through this robot's attachments
                if (!empty($robot->robot_attachments)){
                    foreach ($robot->robot_attachments AS $attachment_token => $attachment_info){
                        $attachment_token_clean = preg_replace('/^ability_([-_a-z0-9]+)$/', '$1', $attachment_token);
                        if (!in_array($attachment_token_clean, $explosive_object_tokens)){ continue; }
                        $attachment_fx_token = false;
                        if (isset($object_fx_tokens[$attachment_token_clean])){
                            $attachment_fx_token = str_replace(
                                $attachment_token_clean,
                                $object_fx_tokens[$attachment_token_clean],
                                $attachment_token
                                );
                            }
                        $positions_of_interest[] = array(
                            'kind' => 'robot',
                            'token' => $attachment_token,
                            'fxtoken' => $attachment_fx_token,
                            'player' => $target_player,
                            'robot' => $robot,
                            );
                    }
                }
                unset($robot);
            }
        }

        // If there aren't any points of interest, we should return now
        if (empty($positions_of_interest)){ return false; }

        // Otherwise, print a message showing that this effect is taking place
        $certain_text = count($positions_of_interest) === 1 ? 'a barrier' : 'some barriers';
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().' took notice of '.$certain_text.' on the field...',
            array(
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key
                )
            );
        $this_robot->reset_frame();

        // Loop through and show the camera looking at them one-by-one
        foreach ($positions_of_interest AS $key => $hazard_to_remove){

            // Show the camera looking at this robot first
            $hazard_to_remove['robot']->set_frame('defend');
            $this_battle->queue_sound_effect('scan-start');
            $this_battle->events_create($this_robot, false, '', '',
                array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $hazard_to_remove['robot']->player->player_side,
                    'event_flag_camera_focus' => $hazard_to_remove['robot']->robot_position,
                    'event_flag_camera_depth' => $hazard_to_remove['robot']->robot_key
                    )
                );
            $hazard_to_remove['robot']->reset_frame();

            // And now we can actually remove the hazard while showing the skill popup for each
            if ($hazard_to_remove['kind'] === 'battle'){
                $this_battle->unset_attachment($hazard_to_remove['key'], $hazard_to_remove['token']);
                if (!empty($hazard_to_remove['fxtoken'])){ $this_battle->unset_attachment($hazard_to_remove['key'], $hazard_to_remove['fxtoken']); }
            } else if ($hazard_to_remove['kind'] === 'robot'){
                $hazard_to_remove['robot']->unset_attachment($hazard_to_remove['token']);
                if (!empty($hazard_to_remove['fxtoken'])){ $hazard_to_remove['robot']->unset_attachment($hazard_to_remove['fxtoken']); }
                if (strstr($hazard_to_remove['token'], 'core-shield_')){ $hazard_to_remove['robot']->set_counter('item_disabled', 2); }
            }

            // Print a message showing that this effect is taking place
            $shield_or_barrier = strstr($hazard_to_remove['token'], 'core-shield_') ? 'shield surrounding' : 'barrier blocking';
            $hazard_to_remove['robot']->set_frame('taunt');
            $this_robot->set_frame('taunt');
            $this_battle->queue_sound_effect('debuff-received');
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                'The protective '.$shield_or_barrier.' '.$hazard_to_remove['robot']->print_name().' was removed!',
                array(
                    'this_skill' => $this_skill,
                    'canvas_show_this_skill_overlay' => false,
                    'canvas_show_this_skill_underlay' => true,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $hazard_to_remove['robot']->player->player_side,
                    'event_flag_camera_focus' => $hazard_to_remove['robot']->robot_position,
                    'event_flag_camera_depth' => $hazard_to_remove['robot']->robot_key
                    )
                );
            $this_robot->reset_frame();
            $hazard_to_remove['robot']->reset_frame();

        }

        // Return true on success
        return true;

    }
);
?>
