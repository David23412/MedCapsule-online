@extends('layouts.admin')

@section('title', 'Payment System Settings')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Payment System Settings</h1>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <div class="col-lg-8">
            <form action="{{ route('payment-settings') }}" method="POST">
                @csrf
                
                <!-- OCR Settings Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                        <h6 class="m-0 font-weight-bold text-primary">
                            OCR Settings
                            @if($ocrAvailable)
                                <span class="badge badge-success">Available</span>
                            @else
                                <span class="badge badge-danger">Not Available</span>
                            @endif
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Enable OCR Verification:</label>
                            <div class="col-sm-8">
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="ocr_enabled" name="ocr_enabled" 
                                           value="1" {{ $settings['ocr']['enabled'] ? 'checked' : '' }}
                                           {{ !$ocrAvailable ? 'disabled' : '' }}>
                                    <label class="custom-control-label" for="ocr_enabled">
                                        {{ $settings['ocr']['enabled'] ? 'Enabled' : 'Disabled' }}
                                    </label>
                                </div>
                                @if(!$ocrAvailable)
                                    <small class="form-text text-danger">
                                        Tesseract OCR is not installed on this server. Install it first to enable OCR verification.
                                    </small>
                                @endif
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="ocr_approval_threshold" class="col-sm-4 col-form-label">OCR Approval Threshold:</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="ocr_approval_threshold" 
                                       name="ocr_approval_threshold" min="1" max="10" 
                                       value="{{ $settings['ocr']['approval_threshold'] }}">
                                <small class="form-text text-muted">
                                    Minimum score (1-10) for automatic approval via OCR.
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="ocr_admin_review_threshold" class="col-sm-4 col-form-label">Admin Review Threshold:</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="ocr_admin_review_threshold" 
                                       name="ocr_admin_review_threshold" min="1" max="10" 
                                       value="{{ $settings['ocr']['admin_review_threshold'] }}">
                                <small class="form-text text-muted">
                                    Minimum score (1-10) to flag for expedited admin review.
                                </small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            OCR verification extracts text from payment receipts to validate reference numbers, 
                            transaction IDs, and amounts. If disabled, the system will rely on transaction pattern 
                            matching and metadata validation.
                        </div>
                    </div>
                </div>
                
                <!-- Verification Settings Card -->
                <div class="card shadow mb-4">
                    <div class="card-header py-3">
                        <h6 class="m-0 font-weight-bold text-primary">Verification Settings</h6>
                    </div>
                    <div class="card-body">
                        <div class="form-group row">
                            <label class="col-sm-4 col-form-label">Auto-Verification:</label>
                            <div class="col-sm-8">
                                <div class="custom-control custom-switch mt-2">
                                    <input type="checkbox" class="custom-control-input" 
                                           id="auto_verification_enabled" name="auto_verification_enabled" 
                                           value="1" {{ $settings['general']['enable_auto_verification'] ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="auto_verification_enabled">
                                        {{ $settings['general']['enable_auto_verification'] ? 'Enabled' : 'Disabled' }}
                                    </label>
                                </div>
                                <small class="form-text text-muted">
                                    If disabled, all payments will require manual admin verification.
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="auto_approval_threshold" class="col-sm-4 col-form-label">Auto-Approval Threshold:</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="auto_approval_threshold" 
                                       name="auto_approval_threshold" min="1" max="100" 
                                       value="{{ $settings['verification']['auto_approval_threshold'] }}">
                                <small class="form-text text-muted">
                                    Confidence score (1-100) required for automatic approval.
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-group row">
                            <label for="expedited_review_threshold" class="col-sm-4 col-form-label">Expedited Review Threshold:</label>
                            <div class="col-sm-8">
                                <input type="number" class="form-control" id="expedited_review_threshold" 
                                       name="expedited_review_threshold" min="1" max="100" 
                                       value="{{ $settings['verification']['expedited_review_threshold'] }}">
                                <small class="form-text text-muted">
                                    Confidence score (1-100) to flag for expedited admin review.
                                </small>
                            </div>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> 
                            The verification system uses a weighted approach combining transaction patterns, 
                            OCR results, and metadata validation. Higher thresholds increase security but 
                            may require more manual reviews.
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Save Settings</button>
                    <a href="{{ route('admin.payment-history') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
        
        <div class="col-lg-4">
            <!-- System Status Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">System Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-2">
                        <span class="font-weight-bold">OCR Verification:</span>
                        @if($ocrAvailable && $settings['ocr']['enabled'])
                            <span class="badge badge-success">Active</span>
                        @elseif($ocrAvailable && !$settings['ocr']['enabled'])
                            <span class="badge badge-warning">Disabled</span>
                        @else
                            <span class="badge badge-danger">Unavailable</span>
                        @endif
                    </div>
                    
                    <div class="mb-2">
                        <span class="font-weight-bold">Auto-Verification:</span>
                        @if($settings['general']['enable_auto_verification'])
                            <span class="badge badge-success">Enabled</span>
                        @else
                            <span class="badge badge-warning">Disabled</span>
                        @endif
                    </div>
                    
                    <div class="mb-2">
                        <span class="font-weight-bold">Storage Management:</span>
                        @if($settings['storage']['enabled'])
                            <span class="badge badge-success">Active</span>
                        @else
                            <span class="badge badge-warning">Disabled</span>
                        @endif
                    </div>
                    
                    <div class="mb-2">
                        <span class="font-weight-bold">Reference Expiry:</span>
                        <span>{{ $settings['general']['reference_expiry_hours'] }} hours</span>
                    </div>
                    
                    <div class="mb-4">
                        <span class="font-weight-bold">Method Weights:</span>
                        <ul class="list-unstyled ml-3 mt-2">
                            <li>Transaction Pattern: {{ $settings['verification']['method_weights']['transaction_pattern'] * 10 }}%</li>
                            <li>OCR: {{ $settings['verification']['method_weights']['ocr'] * 10 }}%</li>
                            <li>Metadata: {{ $settings['verification']['method_weights']['metadata'] * 10 }}%</li>
                        </ul>
                    </div>
                    
                    <div class="text-center">
                        <a href="{{ route('payment-logs') }}" class="btn btn-sm btn-info">View Logs</a>
                        <a href="{{ route('payments.history') }}" class="btn btn-sm btn-secondary">Payment History</a>
                    </div>
                </div>
            </div>
            
            <!-- Quick Tips Card -->
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Tips</h6>
                </div>
                <div class="card-body">
                    <ul class="pl-4">
                        <li class="mb-2">Set OCR threshold to 7+ for high confidence auto-approval.</li>
                        <li class="mb-2">Periodically run the payment proof cleanup job to manage storage.</li>
                        <li class="mb-2">Use the payment logs to audit verification issues.</li>
                        <li class="mb-2">Disable OCR temporarily if the server experiences high load.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Update the toggle switch label text
    $('#ocr_enabled').change(function() {
        $(this).next('label').text(this.checked ? 'Enabled' : 'Disabled');
    });
    
    $('#auto_verification_enabled').change(function() {
        $(this).next('label').text(this.checked ? 'Enabled' : 'Disabled');
    });
</script>
@endsection 