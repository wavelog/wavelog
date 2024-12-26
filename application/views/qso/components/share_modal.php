<div class="container" id="share_modal">
    <a class="btn btn-primary mb-3" target="_blank" href="https://twitter.com/intent/tweet?text=<?php echo urlencode($qso['twitter_string']); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path fill="white" d="M389.2 48h70.6L305.6 224.2 487 464H345L233.7 318.6 106.5 464H35.8L200.7 275.5 26.8 48H172.4L272.9 180.9 389.2 48zM364.4 421.8h39.1L151.1 88h-42L364.4 421.8z"/></svg> <?= __("Post on X"); ?>
    </a>
    <a class="btn btn-primary mb-3" target="_blank" href="https://bsky.app/intent/compose?text=<?php echo urlencode($qso['twitter_string']); ?>">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 512 512"><!--!Font Awesome Free 6.7.2 by @fontawesome - https://fontawesome.com License - https://fontawesome.com/license/free Copyright 2024 Fonticons, Inc.--><path fill="white" d="M111.8 62.2C170.2 105.9 233 194.7 256 242.4c23-47.6 85.8-136.4 144.2-180.2c42.1-31.6 110.3-56 110.3 21.8c0 15.5-8.9 130.5-14.1 149.2C478.2 298 412 314.6 353.1 304.5c102.9 17.5 129.1 75.5 72.5 133.5c-107.4 110.2-154.3-27.6-166.3-62.9l0 0c-1.7-4.9-2.6-7.8-3.3-7.8s-1.6 3-3.3 7.8l0 0c-12 35.3-59 173.1-166.3 62.9c-56.5-58-30.4-116 72.5-133.5C100 314.6 33.8 298 15.7 233.1C10.4 214.4 1.5 99.4 1.5 83.9c0-77.8 68.2-53.4 110.3-21.8z"/></svg> <?= __("Post on Bluesky"); ?>
    </a>
    <?php if ($this->session->userdata('user_mastodon_url') != null) { ?>
        <a class="btn btn-primary mb-3" target="_blank" href="<?php echo $this->session->userdata('user_mastodon_url') . '/share?text=' . urlencode($qso['twitter_string']); ?>">
            <i class="fab fa-mastodon"></i> <?= __("Toot on Mastodon"); ?>
        </a>
    <?php } ?>
</div>