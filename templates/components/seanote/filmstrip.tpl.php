<?php
use function Application\getThumbnailUrl;
?>

<div id="seanote-filmstrip" class="seanote-filmstrip">
    <div class="header">
        <i class="angle double left inverted link icon scroll-backward tipped"
           data-position="top left" data-content="<?php
            echo $this->text->get('seanote.filmstrip.button.scroll_backward'); ?>"></i>
        <i class="selected radio inverted link icon scroll-to-current-page tipped"
           data-position="top left" data-content="<?php
            echo $this->text->get('seanote.filmstrip.button.scroll_to_current_page'); ?>"></i>
        <i class="angle double right inverted link icon scroll-forward tipped"
           data-position="top left" data-content="<?php
            echo $this->text->get('seanote.filmstrip.button.scroll_forward'); ?>"></i>
        <div class="icon ui buttons right floated">
            <i class="close inverted icon tipped"
               data-position="top right" data-content="<?php
                echo $this->text->get('seanote.filmstrip.button.close'); ?>"></i>
        </div>
    </div>
    <div class="body">
        <div class="ui horizontal list">
        <?php
            $i = 0;
            foreach ($this->pages as $page) :
                $i++;
                $properties = $page['properties'];
                $scaleRatio = $properties['width'] / $properties['height'];
                $imageHeight = 100;
                $imageWidth = floor($imageHeight * $scaleRatio);
                $imageSource = getThumbnailUrl(($properties['thumbnail']['@id'] ?? ''), 0, $imageHeight);
                $canvasUrl = $this->canvas_view_url . '/' . $properties['code'];
        ?>
            <div class="item<?php if ($this->canvas->get('id') === $page['id']) echo ' current'; ?>">
                <a href="<?php echo $canvasUrl ?>" class="ui rounded image">
                    <img src="data:image/gif;base64,R0lGODlhAQABAAAAACH5BAEKAAEALAAAAAABAAEAAAICTAEAOw==" data-src="<?php echo $imageSource; ?>"
                         width="<?php echo $imageWidth; ?>" height="<?php echo $imageHeight; ?>"
                         alt="<?php echo $properties['code']; ?>" />
                    <div class="ui label"><?php echo $properties['code']; ?></div>
                </a>
            </div>
        <?php
            endforeach;
        ?>
        </div>
    </div>
</div>
