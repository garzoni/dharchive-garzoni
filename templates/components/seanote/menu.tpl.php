<div id="seanote-menu" class="seanote-menu ui inverted menu">
    <div class="ui dropdown link item">
        <span class="text"><?php
            echo $this->text->get('seanote.menu.document'); ?></span>
        <div class="menu">
            <div class="item undone hidden">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.open'); ?></span>
                <div class="menu">
                    <div class="item">
                        <i class="dropdown icon"></i>
                        <span class="text"><?php
                            echo $this->text->get('seanote.menu.recent'); ?></span>
                        <div class="menu"></div>
                    </div>
                    <div class="item arbitrary-document"><?php
                        echo $this->text->get('seanote.menu.arbitrary'); ?></div>
                </div>
            </div>
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.go_to'); ?></span>
                <div class="menu">
                    <div class="item">
                        <i class="dropdown icon"></i>
                        <span class="text"><?php
                            echo $this->text->get('seanote.menu.page'); ?></span>
                        <div class="menu">
                            <a class="item first-page"
                               href="<?php echo $this->canvas_view_url . '/' . $this->first_page ?>"><?php
                                echo $this->text->get('seanote.menu.first'); ?></a>
                            <a class="item previous-page <?php if (!$this->previous_page) echo ' disabled' ?>"
                               href="<?php echo $this->canvas_view_url . '/' . $this->previous_page ?>"><?php
                                echo $this->text->get('seanote.menu.previous'); ?></a>
                            <a class="item next-page <?php if (!$this->next_page) echo ' disabled' ?>"
                               href="<?php echo $this->canvas_view_url . '/' . $this->next_page ?>"><?php
                                echo $this->text->get('seanote.menu.next'); ?></a>
                            <a class="item last-page"
                               href="<?php echo $this->canvas_view_url . '/' . $this->last_page ?>"><?php
                                echo $this->text->get('seanote.menu.last'); ?></a>
                            <div class="item arbitrary-page undone hidden"><?php
                                echo $this->text->get('seanote.menu.arbitrary'); ?></div>
                        </div>
                    </div>
                    <div class="item undone hidden"><?php
                        echo $this->text->get('seanote.menu.section'); ?></div>
                    <div class="item undone hidden"><?php
                        echo $this->text->get('seanote.menu.bookmark'); ?></div>
                </div>
            </div>
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.export'); ?></span>
                <div class="menu">
                    <div class="header"><?php
                        echo $this->text->get('seanote.menu.iiif'); ?></div>
                    <a class="item export-manifest" target="_blank" href="<?php
                        echo $this->export_url . '/manifest'; ?>"><?php
                        echo $this->text->get('seanote.menu.manifest'); ?></a>
                    <div class="item export-sequence undone hidden"><?php
                        echo $this->text->get('seanote.menu.sequence'); ?></div>
                    <div class="item export-structure undone hidden"><?php
                        echo $this->text->get('seanote.menu.structure'); ?></div>
                    <div class="item export-annotation-layer undone hidden"><?php
                        echo $this->text->get('seanote.menu.annotation_layer'); ?></div>
                </div>
            </div>
            <a class="item close-document" href="<?php
            echo $this->documents_list_url; ?>"><?php
                echo $this->text->get('seanote.menu.close'); ?></a>
            <div class="item about-document undone hidden"><?php
                echo $this->text->get('seanote.menu.about'); ?></div>
        </div>
    </div>
    <div class="ui dropdown link item undone hidden">
        <span class="text"><?php echo $this->text->get('seanote.menu.edit'); ?></span>
        <div class="menu">
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php echo $this->text->get('seanote.menu.annotate'); ?></span>
                <div class="menu">
                    <div class="item annotate-page"><?php
                        echo $this->text->get('seanote.menu.current_page'); ?></div>
                    <div class="item annotate-segment disabled"><?php
                        echo $this->text->get('seanote.menu.selected_segment'); ?></div>
                    <div class="item annotate-segment-group disabled"><?php
                        echo $this->text->get('seanote.menu.selected_segment_group'); ?></div>
                </div>
            </div>
            <div class="item draw-selection disabled"><?php
                echo $this->text->get('seanote.menu.draw_selection'); ?></div>
            <div class="item find"><?php
                echo $this->text->get('seanote.menu.find'); ?></div>
            <div class="item preferences"><?php
                echo $this->text->get('seanote.menu.preferences'); ?></div>
        </div>
    </div>
    <div class="ui dropdown link item">
        <span class="text"><?php
            echo $this->text->get('seanote.menu.view'); ?></span>
        <div class="menu">
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.components'); ?></span>
                <div class="menu">
                    <div class="item show-menu active disabled"><?php
                        echo $this->text->get('seanote.menu.menu'); ?></div>
                    <div class="item show-control-bar active disabled"><?php
                        echo $this->text->get('seanote.menu.controls'); ?></div>
                    <div class="item show-toolbar"><?php
                        echo $this->text->get('seanote.menu.toolbar'); ?></div>
                    <div class="item show-filmstrip"><?php
                        echo $this->text->get('seanote.menu.filmstrip'); ?></div>
                    <div class="item show-navigator"><?php
                        echo $this->text->get('seanote.menu.navigator'); ?></div>
                    <div class="item show-explorer undone hidden"><?php
                        echo $this->text->get('seanote.menu.explorer'); ?></div>
                    <div class="item show-sidebar undone hidden"><?php
                        echo $this->text->get('seanote.menu.sidebar'); ?></div>
                </div>
            </div>
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.page_objects'); ?></span>
                <div class="menu">
                    <div class="item show-segments"><?php
                        echo $this->text->get('seanote.menu.segments'); ?></div>
                    <div class="item show-segment-groups undone hidden"><?php
                        echo $this->text->get('seanote.menu.segment_groups'); ?></div>
                    <div class="item show-segment-links undone hidden"><?php
                        echo $this->text->get('seanote.menu.segment_links'); ?></div>
                    <div class="item show-bookmarks undone hidden"><?php
                        echo $this->text->get('seanote.menu.bookmarks'); ?></div>
                </div>
            </div>
            <div class="divider"></div>
            <div class="item zoom-in"><?php
                echo $this->text->get('seanote.menu.zoom_in'); ?></div>
            <div class="item zoom-out"><?php
                echo $this->text->get('seanote.menu.zoom_out'); ?></div>
            <div class="item zoom-to-actual-size"><?php
                echo $this->text->get('seanote.menu.actual_size'); ?></div>
            <div class="item fit-on-viewer"><?php
                echo $this->text->get('seanote.menu.fit_on_viewer'); ?></div>
        </div>
    </div>
    <div class="ui dropdown link item">
        <span class="text"><?php
            echo $this->text->get('seanote.menu.page'); ?></span>
        <div class="menu">
            <div class="item undone hidden">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.go_to'); ?></span>
                <div class="menu">
                    <div class="item">
                        <i class="dropdown icon"></i>
                        <span class="text"><?php
                            echo $this->text->get('seanote.menu.line'); ?></span>
                        <div class="menu">
                            <div class="item"><?php
                                echo $this->text->get('seanote.menu.first'); ?></div>
                            <div class="item"><?php
                                echo $this->text->get('seanote.menu.previous'); ?></div>
                            <div class="item"><?php
                                echo $this->text->get('seanote.menu.next'); ?></div>
                            <div class="item"><?php
                                echo $this->text->get('seanote.menu.last'); ?></div>
                            <div class="item"><?php
                                echo $this->text->get('seanote.menu.arbitrary'); ?></div>
                        </div>
                    </div>
                    <div class="item"><?php
                        echo $this->text->get('seanote.menu.section'); ?></div>
                    <div class="item"><?php
                        echo $this->text->get('seanote.menu.bookmark'); ?></div>
                </div>
            </div>
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.export'); ?></span>
                <div class="menu">
                    <div class="header"><?php
                        echo $this->text->get('seanote.menu.iiif'); ?></div>
                    <a class="item" target="_blank" href="<?php
                        echo $this->export_url . '/canvas'; ?>"><?php
                        echo $this->text->get('seanote.menu.canvas'); ?></a>
                    <a class="item" target="_blank" href="<?php
                        echo $this->export_url . '/annotations'; ?>"><?php
                        echo $this->text->get('seanote.menu.annotation_list'); ?></a>
                    <div class="divider"></div>
                    <div class="header"><?php
                        echo $this->text->get('seanote.menu.other'); ?></div>
                    <a class="item" target="_blank" href="<?php
                        echo $this->export_url . '/canvas-objects'; ?>"><?php
                        echo $this->text->get('seanote.menu.page_objects'); ?></a>
                    <?php if ($this->app->hasPermission('export_images')) : ?>
                    <div class="divider"></div>
                    <div class="header"><?php
                        echo $this->text->get('seanote.menu.image'); ?></div>
                    <div class="item undone hidden"><?php
                        echo $this->text->get('seanote.menu.whole_page'); ?></div>
                    <div class="item undone hidden"><?php
                        echo $this->text->get('seanote.menu.selected_region'); ?></div>
                    <div class="item undone hidden"><?php
                        echo $this->text->get('seanote.menu.selected_segment'); ?></div>
                    <a class="item" target="_blank" href="<?php
                        echo $this->export_url . '/image/full/full/0/default.jpg'; ?>">JPG</a>
                    <a class="item" target="_blank" href="<?php
                        echo $this->export_url . '/image/full/full/0/default.png'; ?>">PNG</a>
                    <a class="item" target="_blank" href="<?php
                        echo $this->export_url . '/image/full/full/0/default.pdf'; ?>">PDF</a>
                    <a class="item" target="_blank" href="<?php
                        echo $this->export_url . '/image/full/full/0/default.gif'; ?>">GIF</a>
                    <a class="item" target="_blank" href="<?php
                        echo $this->export_url . '/image/full/full/0/default.webp'; ?>">WEBP</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.transform'); ?></span>
                <div class="menu">
                    <div class="item rotate-180"><?php
                        echo $this->text->get('seanote.menu.rotate_180'); ?></div>
                    <div class="item rotate-90-cw"><?php
                        echo $this->text->get('seanote.menu.rotate_90cw'); ?></div>
                    <div class="item rotate-90-ccw"><?php
                        echo $this->text->get('seanote.menu.rotate_90ccw'); ?></div>
                    <div class="item rotate-arbitrary undone hidden"><?php
                        echo $this->text->get('seanote.menu.rotate_arbitrary'); ?></div>
                    <div class="item undone hidden"><?php
                        echo $this->text->get('seanote.menu.flip_horizontal'); ?></div>
                    <div class="item undone hidden"><?php
                        echo $this->text->get('seanote.menu.flip_vertical'); ?></div>
                </div>
            </div>
            <div class="item undone hidden">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.apply_filter'); ?></span>
                <div class="menu">
                    <div class="item"><?php
                        echo $this->text->get('seanote.menu.desaturate'); ?></div>
                    <div class="item"><?php
                        echo $this->text->get('seanote.menu.binarize'); ?></div>
                </div>
            </div>
            <div class="item undone hidden"><?php
                echo $this->text->get('seanote.menu.reset'); ?></div>
            <div class="item undone hidden"><?php
                echo $this->text->get('seanote.menu.about'); ?></div>
        </div>
    </div>
    <div class="ui dropdown link item<?php if (!$this->app->hasPermission('create_annotations')) echo ' hidden'; ?>">
        <span class="text"><?php
            echo $this->text->get('seanote.menu.segment'); ?></span>
        <div class="menu">
            <div class="item">
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.select'); ?></span>
                <i class="dropdown icon"></i>
                <div class="menu">
                    <div class="item select-all-segments">
                        <span class="text"><?php echo $this->text->get('seanote.menu.all'); ?></span>
                    </div>
                    <div class="item deselect-segments">
                        <span class="text"><?php echo $this->text->get('seanote.menu.deselect'); ?></span>
                    </div>
                    <div class="item invert-segment-selection">
                        <span class="text"><?php echo $this->text->get('seanote.menu.inverse'); ?></span>
                    </div>
                </div>
            </div>
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.create'); ?></span>
                <div class="menu">
                    <div class="item create-segment"><?php
                        echo $this->text->get('seanote.menu.single'); ?></div>
                    <div class="item create-multiple-segments"><?php
                        echo $this->text->get('seanote.menu.multiple'); ?></div>
                </div>
            </div>
            <div class="item">
                <i class="dropdown icon"></i>
                <span class="text"><?php
                    echo $this->text->get('seanote.menu.combine'); ?></span>
                <div class="menu">
                    <div class="item merge-segments undone hidden"><?php
                        echo $this->text->get('seanote.menu.merge'); ?></div>
                    <div class="item unmerge-segments undone hidden"><?php
                        echo $this->text->get('seanote.menu.unmerge'); ?></div>
                    <div class="item group-segments"><?php
                        echo $this->text->get('seanote.menu.group'); ?></div>
                    <div class="item ungroup-segments"><?php
                        echo $this->text->get('seanote.menu.ungroup'); ?></div>
                </div>
            </div>
            <div class="item resize-segment"><?php
                echo $this->text->get('seanote.menu.resize'); ?></div>
            <div class="item delete-segments"><?php
                echo $this->text->get('seanote.menu.delete'); ?></div>
        </div>
    </div>
    <div class="ui dropdown link item hidden">
        <span class="text"><?php
            echo $this->text->get('seanote.menu.help'); ?></span>
        <div class="menu">
            <div class="item keyboard-shortcuts undone"><?php
                echo $this->text->get('seanote.menu.keyboard_shortcuts'); ?></div>
            <div class="item application-info undone hidden"><?php
                echo $this->text->get('seanote.menu.application_info'); ?></div>
        </div>
    </div>
</div>
