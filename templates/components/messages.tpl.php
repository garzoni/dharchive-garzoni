<?php

use Application\Core\Type\Map;

$classNames = new Map([
    'success' => 'positive',
    'info' => 'info',
    'warning' => 'warning',
    'error' => 'negative',
]);

foreach ($this->session->getAllMessages() as $type => $messageGroup) :
    foreach ($messageGroup as $message) :
?>
    <div class="ui <?php echo $classNames->get($type); ?> message">
    <?php
        if (is_array($message)):
            $title = array_shift($message);
    ?>
        <?php if (!empty($title)): ?>
        <div class="header"><?php echo $title; ?></div>
        <?php endif; ?>
        <?php if (count($message) === 1): ?>
            <p><?php echo array_shift($message); ?></p>
        <?php else: ?>
            <ul class="list">
            <?php foreach ($message as $msg): ?>
                <li><?php echo $msg; ?></li>
            <?php endforeach; ?>
            </ul>
        <?php endif; ?>
    <?php
         else:
    ?>
        <p><?php echo $message; ?></p>
    <?php
         endif;
    ?>
    </div>
<?php
    endforeach;
endforeach;
unset($classNames);
?>
