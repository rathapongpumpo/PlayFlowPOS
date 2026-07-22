@php
    $isEdit = ($mode ?? 'create') === 'edit';
    $recordId = $formRecord['id'] ?? null;
    $previewKey = $isEdit && $recordId !== null ? 'edit-' . $recordId : 'create';
    $backUrl = route('masseuse', array_filter([
        'branch_id' => $activeBranchId ?? null,
        'date' => $selectedDate ?? null,
    ], static function ($value): bool {
        return $value !== null && $value !== '';
    }));
    $statusValue = old('status', $formRecord['status_value'] ?? 'available');
    $displayName = trim((string) old('full_name', $formRecord['full_name'] ?? '')) !== ''
        ? trim((string) old('full_name', $formRecord['full_name'] ?? ''))
        : trim((string) old('nickname', $formRecord['nickname'] ?? ''));
    $hasExistingImage = !$isEdit ? false : (($formRecord['profile_image'] ?? '') !== '');
    $imageSource = $hasExistingImage ? (string) ($formRecord['avatar'] ?? '') : '';
    $defaultShiftStart = !empty($formRecord['shift_start']) ? \Carbon\Carbon::parse($formRecord['shift_start'])->format('H:i') : '';
    $defaultShiftEnd = !empty($formRecord['shift_end']) ? \Carbon\Carbon::parse($formRecord['shift_end'])->format('H:i') : '';
@endphp

<div class="row g-3">
    <div class="col-12">
        <section class="form-card">
            <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mb-3">
                <div>
                    <h3 class="section-title">{{ $isEdit ? 'แก้ไขข้อมูลหมอนวด' : 'เพิ่มหมอนวด' }}</h3>
                    <div class="section-subtitle">
                        {{ $isEdit ? 'อัปเดตข้อมูลส่วนตัว รูปโปรไฟล์ และสถานะหลักของหมอนวด' : 'บันทึกข้อมูลส่วนตัว รูปโปรไฟล์ และทักษะของหมอนวดคนใหม่' }}
                    </div>
                </div>
                <a href="{{ $backUrl }}" class="btn btn-outline-primary rounded-pill px-3 page-action">
                    <i class="fa-solid fa-arrow-left"></i>
                    <span>กลับหน้าหมอนวด</span>
                </a>
            </div>

            <form method="POST" action="{{ $formAction }}" enctype="multipart/form-data" class="row g-3">
                @csrf
                @if($isEdit)
                @method('PUT')
                @endif

                <input type="hidden" name="branch_id" value="{{ $activeBranchId }}">
                <input type="hidden" name="date" value="{{ $selectedDate }}">

                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold">รูปโปรไฟล์</label>
                    <div class="upload-picker{{ $hasExistingImage ? ' has-image' : '' }}" data-upload-picker="{{ $previewKey }}">
                        <label class="upload-stage" for="profile_image_{{ $previewKey }}">
                            <div class="upload-frame">
                                <img
                                    src="{{ $imageSource }}"
                                    alt="{{ $displayName !== '' ? $displayName : 'รูปโปรไฟล์หมอนวด' }}"
                                    class="upload-preview-image"
                                    data-image-preview="{{ $previewKey }}"
                                >
                                <div class="upload-placeholder" data-image-placeholder="{{ $previewKey }}">
                                    <span class="upload-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                    <div class="upload-title">{{ $hasExistingImage ? 'เปลี่ยนรูปโปรไฟล์' : 'อัปโหลดรูปโปรไฟล์' }}</div>
                                    <div class="upload-subtitle">
                                        {{ $hasExistingImage ? 'แตะเพื่อเลือกรูปใหม่แทนของเดิม' : 'แตะเพื่อเลือกรูปที่ต้องการใช้งานในระบบ' }}
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                    <input
                        id="profile_image_{{ $previewKey }}"
                        type="file"
                        name="profile_image"
                        class="form-control mt-2"
                        accept="image/*"
                        data-compress-image="true"
                        data-preview-target="{{ $previewKey }}"
                    >
                    <div class="upload-note" data-upload-note="{{ $previewKey }}">
                        ระบบจะพยายามย่อรูปให้อัตโนมัติก่อนอัปโหลด เพื่อให้เหมาะกับขนาดที่ระบบรองรับ
                    </div>

                    @if($isEdit)
                    <div class="form-check mt-2">
                        <input
                            class="form-check-input"
                            type="checkbox"
                            value="1"
                            name="remove_profile_image"
                            id="remove_profile_image_{{ $recordId }}"
                            {{ old('remove_profile_image') ? 'checked' : '' }}
                        >
                        <label class="form-check-label small" for="remove_profile_image_{{ $recordId }}">ลบรูปโปรไฟล์เดิม</label>
                    </div>
                    @endif
                </div>

                <div class="col-12 col-md-8">
                    <div class="row g-3">
                        <div class="col-12 col-lg-6">
                            <label class="form-label small fw-bold">ชื่อเล่น</label>
                            <input type="text" name="nickname" class="form-control" value="{{ old('nickname', $formRecord['nickname'] ?? '') }}" required>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label small fw-bold">ชื่อเต็ม</label>
                            <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $formRecord['full_name'] ?? '') }}">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label small fw-bold">สถานะหลัก</label>
                            <select name="status" class="form-select" required>
                                @foreach($statusOptions as $option)
                                <option value="{{ $option['value'] }}" {{ $statusValue === $option['value'] ? 'selected' : '' }}>
                                    {{ $option['label'] }}
                                </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label small fw-bold">ยอดการันตี/วัน (บาท)</label>
                            <input type="number" step="0.01" min="0" name="guarantee_amount" class="form-control" value="{{ old('guarantee_amount', $formRecord['guarantee_amount'] ?? '0.00') }}">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label small fw-bold">เวลาเข้างาน</label>
                            <input type="time" name="shift_start" class="form-control" value="{{ old('shift_start', $defaultShiftStart) }}">
                        </div>
                        <div class="col-12 col-lg-6">
                            <label class="form-label small fw-bold">เวลาออกงาน</label>
                            <input type="time" name="shift_end" class="form-control" value="{{ old('shift_end', $defaultShiftEnd) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">ทักษะ / รายละเอียดเพิ่มเติม</label>
                            <textarea name="skills_description" rows="6" class="form-control" placeholder="เช่น นวดไทย นวดน้ำมัน กดจุด">{{ old('skills_description', $formRecord['skills_description'] ?? '') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="col-12 d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2 mt-1">
                    <div class="helper-text">
                        {{ $isEdit ? 'แก้ไขแล้วกดบันทึกเพื่ออัปเดตข้อมูลบนหน้าหมอนวดทันที' : 'บันทึกแล้วระบบจะนำข้อมูลไปแสดงในหน้าหมอนวดทันที' }}
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ $backUrl }}" class="btn btn-light border rounded-pill px-3">ยกเลิก</a>
                        <button type="submit" class="btn btn-primary rounded-pill px-4 page-action">
                            <i class="fa-solid {{ $isEdit ? 'fa-floppy-disk' : 'fa-user-plus' }}"></i>
                            <span>{{ $isEdit ? 'บันทึกการแก้ไข' : 'บันทึกหมอนวดใหม่' }}</span>
                        </button>
                    </div>
                </div>
            </form>

            @if($isEdit && isset($deleteAction))
            <div class="delete-card">
                <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-2">
                    <div>
                        <div class="fw-bold text-danger">ลบข้อมูลหมอนวด</div>
                        <div class="helper-text">ระบบจะไม่ให้ลบถ้าหมอนวดคนนี้มีประวัติคิวงานหรือรายการขายแล้ว</div>
                    </div>
                    <form method="POST" action="{{ $deleteAction }}" onsubmit="return confirm('ต้องการลบข้อมูลหมอนวด {{ $displayName !== '' ? $displayName : ($formRecord['nickname'] ?? '') }} ใช่หรือไม่?')">
                        @csrf
                        @method('DELETE')
                        <input type="hidden" name="branch_id" value="{{ $activeBranchId }}">
                        <input type="hidden" name="date" value="{{ $selectedDate }}">
                        <button type="submit" class="btn btn-outline-danger rounded-pill px-3 page-action">
                            <i class="fa-solid fa-trash-can"></i>
                            <span>ลบหมอนวด</span>
                        </button>
                    </form>
                </div>
            </div>
            @endif
        </section>
    </div>
</div>
