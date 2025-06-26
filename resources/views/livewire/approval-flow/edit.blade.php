@push('approval-flow')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.css">
    <script src="https://cdn.jsdelivr.net/gh/jerosoler/Drawflow/dist/drawflow.min.js"></script>
    <link rel="stylesheet" href="{{asset('vendor/approval-flow/approval-flow.css')}}">
@endpush
<div>
    <flux:input label="{{__('Flow name')}}" wire:model="name"/>
    <div x-data="FlowEditor()" class="p-4" wire:ignore>
        <div class="m-2 flex justify-end">
            <flux:button variant="primary" @click="saveflow">{{__('Save flow')}}</flux:button>
        </div>
        <div class="flex flex-row">
            <div class="w-1/4">
                <div class="drag-drawflow" draggable="true" @dragstart="drag($event)" data-node="request">
                    <span>承認依頼</span>
                </div>
                <div class="drag-drawflow" draggable="true" @dragstart="drag($event)" data-node="and">
                    <span>And承認</span>
                </div>
                <div class="drag-drawflow" draggable="true" @dragstart="drag($event)" data-node="or">
                    <span>Or承認</span>
                </div>
                <div class="drag-drawflow" draggable="true" @dragstart="drag($event)" data-node="mail">
                    <span>メール通知</span>
                </div>
            </div>
            <div id="drawflow" class="w-3/4" @dragover="allowDrop($event)" @drop="drop($event)" style="width: 100%; height: 600px; border: 1px solid #ccc;"></div>

        </div>
    </div>
</div>
@script
<script>
    Alpine.data('FlowEditor', () => ({
        editor: null,
        init() {
            // Drawflowを初期化
            this.editor = new Drawflow(document.getElementById('drawflow'));
            this.editor.start();

            this.editor.addNode(
                'start',
                0, // Inputs
                1, // Outputs
                100, // x位置
                100, // y位置
                'start', // クラス名
                { type: 'start'}, // データ
                '<div>申請</div>' // HTMLテンプレート
            );
            this.editor.addNode(
                'end',
                1, // Inputs
                0, // Outputs
                400, // x位置
                100, // y位置
                'end', // クラス名
                {type: 'end'}, // データ
                '<div>承認</div>' // HTMLテンプレート
            );
            const fdata=$wire.get('flow_data');
            if(Array.isArray(fdata) && fdata.length > 0){
                this.loadWorkflow(fdata)
            }
        },
        allowDrop(ev) {
            ev.preventDefault();
        },
        drag(ev) {
            if (ev.type === "touchstart") {
                mobile_item_selec = ev.target.closest(".drag-drawflow").getAttribute('data-node');
            } else {
                ev.dataTransfer.setData("node", ev.target.getAttribute('data-node'));
            }
        },

        drop(ev) {
            if (ev.type === "touchend") {
                var parentdrawflow = document.elementFromPoint( mobile_last_move.touches[0].clientX, mobile_last_move.touches[0].clientY).closest("#drawflow");
                if(parentdrawflow != null) {
                    this.addNodeToDrawFlow(mobile_item_selec, mobile_last_move.touches[0].clientX, mobile_last_move.touches[0].clientY);
                }
                mobile_item_selec = '';
            } else {
                ev.preventDefault();
                var data = ev.dataTransfer.getData("node");
                this.addNodeToDrawFlow(data, ev.clientX, ev.clientY);
            }

        },
        addNodeToDrawFlow(name, pos_x, pos_y) {
            if(this.editor.editor_mode === 'fixed') {
                return false;
            }
            pos_x = pos_x * ( this.editor.precanvas.clientWidth / (this.editor.precanvas.clientWidth * this.editor.zoom)) - (this.editor.precanvas.getBoundingClientRect().x * ( this.editor.precanvas.clientWidth / (this.editor.precanvas.clientWidth * this.editor.zoom)));
            pos_y = pos_y * ( this.editor.precanvas.clientHeight / (this.editor.precanvas.clientHeight * this.editor.zoom)) - (this.editor.precanvas.getBoundingClientRect().y * ( this.editor.precanvas.clientHeight / (this.editor.precanvas.clientHeight * this.editor.zoom)));

            let html = '';
            let data = {};
            let nodeType = name;
            let input = 1;
            let output = 1;

            switch (name) {
                case 'request':
                    html = `
                        <div>
                            <div class="title-box">承認依頼</div>
                            <div class="box">
                                <p>承認者を選択</p>
                                <select df-post>
                                    <option value="">承認者を選択</option>
                                    @foreach($PostList as $p)
                    <option value="{{$p->id}}">{{$p->name}}</option>
                                    @endforeach
                    </select>
                    <p>システム承認者を選択</p>
                                <input type="number" df-system>
                </div>
            </div>
`;
                    data = { "post": '',"system": '' };
                    output = 1;
                    break;
                case 'mail':
                    html = `
                        <div>
                            <div class="title-box">メール通知</div>
                            <div class="box">
                                <p>通知先を選択</p>
                                <select df-post>
                                    <option value="">通知先を選択</option>
                                    <option value="0">申請者</option>
                                    @foreach($PostList as $p)
                    <option value="{{$p->id}}">{{$p->name}}</option>
                                    @endforeach
                    </select>
                    <p>システム通知先を選択</p>
                                <input type="number" df-system>
                    <p>内容</p>
                    <textarea df-contents></textarea>
                </div>
            </div>
`;
                    data = { "post": '' , "contents": '',"system": '' , };
                    output = 1;
                    break;
                case 'and':
                    html = `
                        <div>
                            <div class="title-box">And承認</div>
                        </div>
                    `;
                    output = 2;
                    break;
                case 'or':
                    html = `
                        <div>
                            <div class="title-box">Or承認</div>
                        </div>
                    `;
                    output = 2;
                    break;
                default:
            }

            this.editor.addNode(name, input, output, pos_x, pos_y, nodeType, data, html );
        },

        saveflow(){
            const workflowData = this.editor.export();
            $wire.save(workflowData);
        },

        loadWorkflow(fdata) {
            this.editor.import(fdata);
        }
    }))
</script>
@endscript
