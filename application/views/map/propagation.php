<div class="ham-dash-shell">
    <iframe
        class="ham-dash-frame"
        src="<?= site_url('ham-dash/'); ?>"
        title="<?= html_escape(__("Propagation Map")); ?>"
        loading="eager"
        referrerpolicy="same-origin"
    ></iframe>
</div>

<style>
.ham-dash-shell {
    height: calc(100vh - 56px);
    min-height: 620px;
    overflow: hidden;
}

.ham-dash-frame {
    border: 0;
    display: block;
    height: 100%;
    width: 100%;
}

@media (max-width: 760px) {
    .ham-dash-shell {
        height: calc(100vh - 50px);
        min-height: 540px;
    }
}
</style>
