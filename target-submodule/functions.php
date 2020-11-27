<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-ability_elemental-shot_onload_before' => function($objects){
        extract($objects);
        $this_ability->set_target('select_target');
        return true;
    },
    'rpg-ability_elemental-buster_onload_after' => function($objects){
        extract($objects);
        if (!$options->buster_charge_required){ $this_ability->set_target('select_target'); }
        return true;
    }
);
?>
