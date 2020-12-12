<?
$functions = array(
    'skill_function' => function($objects){
        return true;
    },
    'rpg-robot_calculate-weapon-energy_after' => function($objects){
        extract($objects);
        if ($options->energy_new > 0 && !empty($this_ability->ability_type)){
            $options->energy_new = ceil($options->energy_new / 2);
        }
    }
);
?>
