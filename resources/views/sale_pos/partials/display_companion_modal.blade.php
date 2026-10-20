<div class="modal fade" id="display-companion-modal" tabindex="-1" role="dialog" aria-labelledby="display-companion-title"
    data-status="{{ route('pos.display-companion.status') }}" data-code="{{ route('pos.display-companion.code') }}"
    data-publish="{{ route('pos.display-companion.publish') }}" data-revoke="{{ route('pos.display-companion.revoke') }}">
    <div class="modal-dialog" role="document"><div class="modal-content">
        <div class="modal-header"><button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button><h4 class="modal-title" id="display-companion-title">Desktop customer display</h4></div>
        <div class="modal-body">
            <p>Open AdomGrace Legacy POS Customer Display on the POS computer. Enter this server address and a pairing code in the app.</p>
            <label for="display-companion-server">Server address</label>
            <input id="display-companion-server" class="form-control" value="{{ url('/') }}" readonly>
            <p class="help-block">Each register browser has its own pairing. Keep one POS tab open for this register.</p>
            <button type="button" class="btn btn-primary" id="display-companion-code-button">Generate pairing code</button>
            <p id="display-companion-code" class="help-block" role="status" style="font-size:24px;letter-spacing:.12em"></p>
            <label><input type="checkbox" id="display-companion-preferred"> Use the desktop app for automatic customer display</label>
            <p class="help-block">Uses your business auto-open setting. The existing customer display button still opens the browser version.</p>
            <div class="btn-group"><button type="button" class="btn btn-default" id="display-companion-show">Show desktop display</button><button type="button" class="btn btn-default" id="display-companion-hide">Hide desktop display</button></div>
            <hr><strong>Paired displays</strong><div id="display-companion-devices"></div>
            <p id="display-companion-status" role="status" class="help-block"></p>
        </div>
        <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div>
    </div></div>
</div>
