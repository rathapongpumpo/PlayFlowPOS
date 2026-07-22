@extends('layouts.main')

@section('title', 'เพิ่มหมอนวด | PlayFlow Spa POS')
@section('page_title', 'เพิ่มหมอนวด')
@section('page_subtitle', 'บันทึกข้อมูลส่วนตัวและรูปโปรไฟล์')

@push('head')
@include('masseuse.partials.styles')
@endpush

@section('content')
<div class="row g-3 masseuse-page">
    @if($errors->any())
    <div class="col-12">
        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-0">
            <div class="fw-bold mb-1">บันทึกข้อมูลไม่สำเร็จ</div>
            <ul class="mb-0 ps-3">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
    @endif

    @if(!($moduleReady ?? false))
    <div class="col-12">
        <div class="alert alert-warning border-0 shadow-sm rounded-4 mb-0">
            <div class="fw-bold mb-1">ยังไม่พร้อมใช้งาน</div>
            <div>ยังไม่พบตาราง <code>masseuses</code> ในฐานข้อมูล</div>
        </div>
    </div>
    @else
    <div class="col-12">
        <section class="hero-card p-3 p-lg-4">
            <div class="row g-3 align-items-end position-relative">
                <div class="col-12 col-xl-6">
                    <div class="hero-title">เพิ่มหมอนวด</div>
                    <p class="hero-subtitle mb-0 mt-2">
                        แยกหน้าเพิ่มข้อมูลออกจากหน้า list เพื่อให้ flow ทำงานเร็วขึ้นและลดความสับสนเวลาจัดการข้อมูลจริง
                    </p>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="hero-metric">
                                <span class="hero-metric-label">สาขาที่ใช้งาน</span>
                                <div class="hero-metric-value">{{ number_format((int) ($activeBranchId ?? 0)) }}</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="hero-metric">
                                <span class="hero-metric-label">วันที่อ้างอิง</span>
                                <div class="hero-metric-value">{{ $selectedDate }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <div class="col-12">
        @include('masseuse.partials.form', [
            'mode' => 'create',
            'formRecord' => $formRecord,
            'formAction' => route('masseuse.store'),
        ])
    </div>
    
    </div>
    @endif
</div>
@endsection

@push('scripts')
@include('masseuse.partials.upload-script')
@endpush
