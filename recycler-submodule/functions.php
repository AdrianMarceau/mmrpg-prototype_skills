<?
$functions = array(
    'skill_function' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Return true on success
        return true;

    },
    'rpg-robot_check-skills_check-recycle-memory' => function($objects){

        // Extract all objects into the current scope
        extract($objects);

        // Take note of each team member's current item so we can monitor if it changes
        $recycle_memory = $this_robot->get_value('recycle_memory');
        if (empty($recycle_memory)){ $recycle_memory = array(); }
        $this_player_robots = $this_player->get_robots();
        foreach ($this_player_robots AS $key => $robot){
            if (isset($recycle_memory[$robot->robot_string])){ continue; }
            $recycle_memory[$robot->robot_string] = $robot->get_item();
            }
        $this_robot->set_value('recycle_memory', $recycle_memory);

        // Define a simple downgrade tree for each lineage of consumable items
        $recycle_downgrade_tree = array();
        $recycle_downgrade_tree[] = array('small-screw');
        $recycle_downgrade_tree[] = array('large-screw', 'small-screw');
        $recycle_downgrade_tree[] = array('weapon-tank', 'weapon-capsule', 'weapon-pellet', 'small-screw');
        $recycle_downgrade_tree[] = array('energy-tank', 'energy-capsule', 'energy-pellet', 'small-screw');
        $recycle_downgrade_tree[] = array('attack-capsule', 'attack-pellet', 'small-screw');
        $recycle_downgrade_tree[] = array('defense-capsule', 'defense-pellet', 'small-screw');
        $recycle_downgrade_tree[] = array('speed-capsule', 'speed-pellet', 'small-screw');
        $recycle_downgrade_tree[] = array('yashichi', 'weapon-pellet', 'small-screw');
        $recycle_downgrade_tree[] = array('extra-life', 'energy-pellet', 'small-screw');

        // Compare the old and new recycle memories to see if anything changed
        $recycle_memory_changed = false;
        $recycle_memory_ideas = array();
        $this_player_robots = $this_player->get_robots_active();
        foreach ($this_player_robots AS $key => $robot){
            $old_item = isset($recycle_memory[$robot->robot_string]) ? $recycle_memory[$robot->robot_string] : false;
            if ($robot->get_item() === $old_item){ continue; }
            $new_item = $robot->get_item();
            $recycle_memory_changed = true;
            $recycle_memory[$robot->robot_string] = $new_item;
            if (empty($new_item)
                && !empty($robot->robot_base_item)){
                // Loop through downgrade tree to find first instance of the new old item's token
                foreach ($recycle_downgrade_tree AS $key2 => $tree){
                    $item_position = array_search($old_item, $tree);
                    if ($item_position === false){ continue; }
                    if ($item_position === count($tree) - 1){ continue; }
                    $new_item = isset($tree[$item_position + 1]) ? $tree[$item_position + 1] : false;
                    if (!empty($new_item)){
                        $recycle_memory_ideas[] = array(
                            'robot' => $robot,
                            'old_item' => $old_item,
                            'new_item' => $new_item,
                            );
                    }
                    break;
                }

            }
        }


        // Update this robot's recycle memory with any changes we've made
        $this_robot->set_value('recycle_memory', $recycle_memory);

        // If nothing has changed or there are no ideas then this skill returns early
        if (empty($recycle_memory_ideas)){ return false; }

        // Otherwise, print a message showing that this effect is taking place
        $certain_text = count($recycle_memory_ideas) === 1 ? 'a consumed item' : 'some consumed items';
        $this_robot->set_frame('taunt');
        $this_battle->queue_sound_effect('scan-start');
        $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
            $this_robot->print_name().' started recycling '.$certain_text.' from earlier in the battle...',
            array(
                'event_flag_camera_action' => true,
                'event_flag_camera_side' => $this_robot->player->player_side,
                'event_flag_camera_focus' => $this_robot->robot_position,
                'event_flag_camera_depth' => $this_robot->robot_key
                )
            );
        $this_robot->reset_frame();

        // Loop through and show the camera looking at them one-by-one
        foreach ($recycle_memory_ideas AS $key => $item_to_recycle){

            // Show the camera looking at this robot first
            $item_to_recycle['robot']->set_frame('defend');
            $this_battle->events_create($this_robot, false, '', '',
                array(
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $item_to_recycle['robot']->player->player_side,
                    'event_flag_camera_focus' => $item_to_recycle['robot']->robot_position,
                    'event_flag_camera_depth' => $item_to_recycle['robot']->robot_key
                    )
                );
            $item_to_recycle['robot']->reset_frame();

            // And now we can actually attach the newly recycled item for this robot
            $item_to_recycle['robot']->set_item($item_to_recycle['new_item']);
            $item_to_recycle['robot']->equip_held_item($item_to_recycle['new_item']);

            // Print a message showing that this effect is taking place
            $item_to_recycle['robot']->set_frame('taunt');
            $this_robot->set_frame('taunt');
            $this_battle->queue_sound_effect('debuff-received');
            $this_battle->events_create($this_robot, false, $this_robot->robot_name.'\'s '.$this_skill->skill_name,
                $this_robot->print_name().'\'s '.$this_skill->print_name().' skill kicked in!<br />'.
                $item_to_recycle['robot']->print_name().'\'s previously-used item was recycled into something new!',
                array(
                    'this_skill' => $this_skill,
                    'canvas_show_this_skill_overlay' => false,
                    'canvas_show_this_skill_underlay' => true,
                    'event_flag_camera_action' => true,
                    'event_flag_camera_side' => $item_to_recycle['robot']->player->player_side,
                    'event_flag_camera_focus' => $item_to_recycle['robot']->robot_position,
                    'event_flag_camera_depth' => $item_to_recycle['robot']->robot_key
                    )
                );
            $this_robot->reset_frame();
            $item_to_recycle['robot']->reset_frame();

        }

        // Return true on success
        return true;

    }
);
$functions['rpg-robot_check-skills_turn-start'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_check-recycle-memory']($objects, true);
};
$functions['rpg-robot_check-skills_end-of-turn'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_check-recycle-memory']($objects, true);
};
$functions['rpg-robot_check-items_end-of-turn'] = function($objects) use ($functions){
    return $functions['rpg-robot_check-skills_check-recycle-memory']($objects, true);
};
?>
