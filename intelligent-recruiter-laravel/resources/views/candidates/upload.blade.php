@extends('layouts.app')

@section('content')
<style>
.upload-wrap{max-width:600px;margin:3rem auto}
.upload-card{background:#fff;border:0.5px solid #e2e8f0;border-radius:12px;padding:2rem 2.25rem}
.upload-card h1{font-size:22px;font-weight:500;color:#0f172a;margin-bottom:6px}
.upload-card p{font-size:13px;color:#64748b;margin-bottom:2rem;line-height:1.6}
.field-label{display:block;font-size:11px;font-weight:500;letter-spacing:0.05em;text-transform:uppercase;color:#64748b;margin-bottom:8px}
.dropzone{border:1.5px dashed #cbd5e1;border-radius:10px;background:#f8fafc;padding:2.5rem 1.5rem;text-align:center;cursor:pointer;transition:border-color .15s,background .15s;position:relative}
.dropzone:hover,.dropzone.dragover{border-color:#185FA5;background:#EFF6FF}
.dropzone i{font-size:32px;color:#94a3b8;display:block;margin-bottom:10px}
.dropzone-main{font-size:14px;font-weight:500;color:#334155;margin-bottom:4px}
.dropzone-sub{font-size:12px;color:#94a3b8}
.dropzone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;width:100%;height:100%}
.file-chosen{margin-top:10px;display:none;align-items:center;gap:8px;padding:8px 12px;background:#EAF3DE;border-radius:8px}
.file-chosen i{font-size:16px;color:#3B6D11}
.file-chosen span{font-size:13px;color:#27500A;font-weight:500}
.file-chosen .file-clear{margin-left:auto;background:none;border:none;cursor:pointer;color:#64748b;font-size:16px;line-height:1}
.file-chosen .file-clear:hover{color:#ef4444}
.error-msg{font-size:12px;color:#791F1F;background:#FCEBEB;border-radius:6px;padding:6px 10px;margin-top:8px}
.btn-submit{display:inline-flex;align-items:center;gap:8px;background:#185FA5;color:#fff;border:none;padding:10px 20px;border-radius:8px;font-size:13px;font-weight:500;cursor:pointer;margin-top:1.75rem}
.btn-submit:hover{background:#0C447C}
.btn-submit:disabled{opacity:0.5;cursor:not-allowed}
.progress-wrap{margin-top:1.25rem;display:none}
.progress-label{display:flex;justify-content:space-between;font-size:12px;color:#64748b;margin-bottom:6px}
.progress-label .progress-status{font-weight:500;color:#185FA5}
.progress-track{height:6px;background:#e2e8f0;border-radius:999px;overflow:hidden}
.progress-bar{height:100%;width:0%;background:linear-gradient(90deg,#185FA5,#38BDF8);border-radius:999px;transition:width 0.4s ease}
</style>

<div class="upload-wrap">
    <div class="upload-card">
        <h1>Upload candidate CV</h1>
        <p>Upload a PDF or DOCX file. The AI service will scan and save structured candidate data into the database.</p>

        <form action="{{ route('candidates.store') }}" method="POST" enctype="multipart/form-data" id="upload-form">
            @csrf
            <div>
                <label class="field-label">Candidate CV</label>

                <div class="dropzone" id="dropzone">
                    <i class="ti ti-cloud-upload" aria-hidden="true"></i>
                    <div class="dropzone-main">Drop file here or click to browse</div>
                    <div class="dropzone-sub">Accepted formats: PDF, DOCX</div>
                    <input type="file" name="resume" accept=".pdf,.docx" required id="file-input">
                </div>

                <div class="file-chosen" id="file-chosen">
                    <i class="ti ti-file-text" aria-hidden="true"></i>
                    <span id="file-name"></span>
                    <button type="button" class="file-clear" id="file-clear" title="Remove file">✕</button>
                </div>

                @error('resume')
                    <p class="error-msg">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit" class="btn-submit" id="btn-submit" disabled>
                <i class="ti ti-scan" aria-hidden="true"></i>
                Scan CV and save
            </button>

            <div class="progress-wrap" id="progress-wrap">
                <div class="progress-label">
                    <span class="progress-status" id="progress-status">Uploading...</span>
                    <span id="progress-pct">0%</span>
                </div>
                <div class="progress-track">
                    <div class="progress-bar" id="progress-bar"></div>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
const input       = document.getElementById('file-input');
const dropzone    = document.getElementById('dropzone');
const chosen      = document.getElementById('file-chosen');
const fileName    = document.getElementById('file-name');
const clearBtn    = document.getElementById('file-clear');
const submit      = document.getElementById('btn-submit');
const progressWrap= document.getElementById('progress-wrap');
const progressBar = document.getElementById('progress-bar');
const progressPct = document.getElementById('progress-pct');
const progressSts = document.getElementById('progress-status');

function showFile(file) {
    if (!file) return;
    fileName.textContent = file.name;
    chosen.style.display = 'flex';
    submit.disabled = false;
}

function clearFile() {
    input.value = '';
    chosen.style.display = 'none';
    fileName.textContent = '';
    submit.disabled = true;
}

function setProgress(pct, label) {
    progressBar.style.width = pct + '%';
    progressPct.textContent = Math.round(pct) + '%';
    if (label) progressSts.textContent = label;
}

input.addEventListener('change', () => showFile(input.files[0]));
clearBtn.addEventListener('click', clearFile);

dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('dragover'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('dragover'));
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('dragover');
    const file = e.dataTransfer.files[0];
    if (file) {
        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;
        showFile(file);
    }
});

document.getElementById('upload-form').addEventListener('submit', function(e) {
    e.preventDefault();

    // Lock UI
    submit.disabled = true;
    clearBtn.disabled = true;
    progressWrap.style.display = 'block';
    setProgress(0, 'Uploading...');

    const formData = new FormData(this);
    const xhr = new XMLHttpRequest();
    let processingInterval = null;

    // Stage 1: real upload progress (0 → 30%)
    xhr.upload.onprogress = function(e) {
        if (e.lengthComputable) {
            const pct = (e.loaded / e.total) * 30;
            setProgress(pct, 'Uploading...');
        }
    };

    xhr.upload.onload = function() {
        // Stage 2: AI processing animation (30 → 95%)
        setProgress(30, 'Scanning CV...');
        let current = 30;
        processingInterval = setInterval(() => {
            // Slow down as it gets closer to 95
            const step = (95 - current) * 0.04;
            current = Math.min(current + Math.max(step, 0.3), 95);
            const labels = [
                { at: 30, text: 'Scanning CV...' },
                { at: 50, text: 'Extracting information...' },
                { at: 70, text: 'Classifying candidate...' },
                { at: 88, text: 'Saving to database...' },
            ];
            const label = [...labels].reverse().find(l => current >= l.at);
            setProgress(current, label ? label.text : 'Processing...');
        }, 400);
    };

    // Stage 3: done → 100% then redirect
    xhr.onload = function() {
        clearInterval(processingInterval);
        setProgress(100, 'Done!');
        setTimeout(() => { window.location.href = xhr.responseURL; }, 600);
    };

    xhr.onerror = function() {
        clearInterval(processingInterval);
        progressSts.textContent = 'Upload failed. Please try again.';
        progressSts.style.color = '#dc2626';
        submit.disabled = false;
        clearBtn.disabled = false;
    };

    xhr.open('POST', this.action);
    xhr.send(formData);
});
</script>
@endsection
