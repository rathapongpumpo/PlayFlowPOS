@extends('layouts.main')

@section('title', 'แก้ไขหมอนวด | PlayFlow Spa POS')
@section('page_title', 'แก้ไขหมอนวด')
@section('page_subtitle', 'อัปเดตข้อมูลส่วนตัวและรูปโปรไฟล์')

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

    <div class="col-12">
        <section class="hero-card p-3 p-lg-4">
            <div class="row g-3 align-items-end position-relative">
                <div class="col-12 col-xl-6">
                    <div class="hero-title">แก้ไขข้อมูลหมอนวด</div>
                    <p class="hero-subtitle mb-0 mt-2">
                        หน้าแก้ไขใช้โครงเดียวกับหน้าเพิ่มหมอนวด แต่ดึงข้อมูลเดิม รายได้ คอมมิชชั่น และคิวล่าสุดขึ้นมาให้ตรวจสอบก่อนบันทึก
                    </p>
                </div>
                <div class="col-12 col-xl-6">
                    <div class="row g-2">
                        <div class="col-6">
                            <div class="hero-metric">
                                <span class="hero-metric-label">รหัสหมอนวด</span>
                                <div class="hero-metric-value">{{ $formRecord['display_id'] ?? '-' }}</div>
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
            'mode' => 'edit',
            'formRecord' => $formRecord,
            'formAction' => route('masseuse.update', ['staffId' => $formRecord['id']]),
            'deleteAction' => route('masseuse.destroy', ['staffId' => $formRecord['id']]),
        ])
    </div>
    
</div>
@endsection

@push('scripts')
@include('masseuse.partials.upload-script')
@endpush
