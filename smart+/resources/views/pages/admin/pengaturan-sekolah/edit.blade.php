@extends('layouts.app')

@section('content')
<div class="mx-auto max-w-screen-2xl p-4 md:p-6 2xl:p-10">
    <div class="mb-6">
        <h2 class="text-2xl font-semibold text-gray-800 dark:text-white/90">Pengaturan Sekolah</h2>
    </div>

    @if(session('success'))
        <div class="mb-6 rounded-lg bg-success-50 p-4 text-sm text-success-700 dark:bg-success-500/10 dark:text-success-400">
            {{ session('success') }}
        </div>
    @endif

    <div class="rounded-2xl border border-gray-200 bg-white p-6 dark:border-gray-800 dark:bg-white/[0.03]">
        <form action="{{ route('admin.pengaturan-sekolah.update') }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Nama Sekolah <span class="text-error-500">*</span></label>
                    <input type="text" name="nama_sekolah" value="{{ old('nama_sekolah', $pengaturan->nama_sekolah ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('nama_sekolah')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Alamat Sekolah</label>
                    <textarea name="alamat_sekolah" rows="3"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">{{ old('alamat_sekolah', $pengaturan->alamat_sekolah ?? '') }}</textarea>
                    @error('alamat_sekolah')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">No. Telepon Sekolah</label>
                    <input type="text" name="no_telepon_sekolah" value="{{ old('no_telepon_sekolah', $pengaturan->no_telepon_sekolah ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('no_telepon_sekolah')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Email Sekolah</label>
                    <input type="email" name="email_sekolah" value="{{ old('email_sekolah', $pengaturan->email_sekolah ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('email_sekolah')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="md:col-span-2">
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Logo Sekolah</label>
                    @if(!empty($pengaturan->logo) && file_exists(public_path('storage/' . $pengaturan->logo)))
                        <div class="mb-3">
                            <img src="{{ asset('storage/' . $pengaturan->logo) }}" alt="Logo Sekolah" class="h-20 w-20 rounded-lg object-cover border border-gray-200 dark:border-gray-700">
                        </div>
                    @endif
                    <input type="file" name="logo" accept="image/jpg,image/jpeg,image/png"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('logo')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam Masuk</label>
                    <input type="time" name="jam_masuk" value="{{ old('jam_masuk', substr($pengaturan->jam_masuk ?? '', 0, 5)) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('jam_masuk')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Jam Pulang</label>
                    <input type="time" name="jam_pulang" value="{{ old('jam_pulang', substr($pengaturan->jam_pulang ?? '', 0, 5)) }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('jam_pulang')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Toleransi Terlambat (menit)</label>
                    <input type="number" name="toleransi_terlambat_menit" value="{{ old('toleransi_terlambat_menit', $pengaturan->toleransi_terlambat_menit ?? '') }}"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('toleransi_terlambat_menit')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Toleransi Alpha (menit)</label>
                    <p class="mb-1.5 text-xs text-gray-400">Menit setelah jam pelajaran selesai sebelum status otomatis Alpha</p>
                    <input type="number" name="toleransi_alpha_menit" value="{{ old('toleransi_alpha_menit', $pengaturan->toleransi_alpha_menit ?? 30) }}"
                        min="0" max="120"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('toleransi_alpha_menit')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Radius Absensi Default (meter)</label>
                    <p class="mb-1.5 text-xs text-gray-400">Dipakai jika titik lokasi tidak memiliki radius khusus</p>
                    <input type="number" name="radius_absensi_meter" value="{{ old('radius_absensi_meter', $pengaturan->radius_absensi_meter ?? 100) }}"
                        min="1"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30" />
                    @error('radius_absensi_meter')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Metode Absensi Siswa <span class="text-error-500">*</span></label>
                    <p class="mb-1.5 text-xs text-gray-400">Pilih mode absensi yang tersedia di dashboard siswa &amp; kiosk</p>
                    <select name="metode_absensi_siswa"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        <option value="RFID" {{ old('metode_absensi_siswa', $pengaturan->metode_absensi_siswa ?? 'RFID') == 'RFID' ? 'selected' : '' }}>RFID (Tap Kiosk)</option>
                        <option value="Hybrid" {{ old('metode_absensi_siswa', $pengaturan->metode_absensi_siswa ?? '') == 'Hybrid' ? 'selected' : '' }}>Hybrid (RFID Kiosk + GPS Lokasi)</option>
                        <option value="Zonasi" {{ old('metode_absensi_siswa', $pengaturan->metode_absensi_siswa ?? '') == 'Zonasi' ? 'selected' : '' }}>Zonasi (GPS Lokasi)</option>
                    </select>
                    @error('metode_absensi_siswa')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Metode Absensi Guru <span class="text-error-500">*</span></label>
                    <select name="metode_absensi_guru"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        <option value="RFID" {{ old('metode_absensi_guru', $pengaturan->metode_absensi_guru ?? 'RFID') == 'RFID' ? 'selected' : '' }}>RFID (Tap Kiosk)</option>
                        <option value="Hybrid" {{ old('metode_absensi_guru', $pengaturan->metode_absensi_guru ?? '') == 'Hybrid' ? 'selected' : '' }}>Hybrid (RFID Kiosk + GPS Lokasi)</option>
                        <option value="Zonasi" {{ old('metode_absensi_guru', $pengaturan->metode_absensi_guru ?? '') == 'Zonasi' ? 'selected' : '' }}>Zonasi (GPS Lokasi)</option>
                    </select>
                    @error('metode_absensi_guru')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400">Metode Absensi Staff Akademik &amp; Kepala Sekolah <span class="text-error-500">*</span></label>
                    <select name="metode_absensi_staff"
                        class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30">
                        <option value="RFID" {{ old('metode_absensi_staff', $pengaturan->metode_absensi_staff ?? 'RFID') == 'RFID' ? 'selected' : '' }}>RFID (Tap Kiosk)</option>
                        <option value="Hybrid" {{ old('metode_absensi_staff', $pengaturan->metode_absensi_staff ?? '') == 'Hybrid' ? 'selected' : '' }}>Hybrid (RFID Kiosk + GPS Lokasi)</option>
                        <option value="Zonasi" {{ old('metode_absensi_staff', $pengaturan->metode_absensi_staff ?? '') == 'Zonasi' ? 'selected' : '' }}>Zonasi (GPS Lokasi)</option>
                    </select>
                    @error('metode_absensi_staff')
                        <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div class="mt-8 rounded-xl border border-gray-200 bg-gray-50/70 p-5 dark:border-gray-800 dark:bg-gray-900/50">
                <div class="mb-4 flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-base font-semibold text-gray-800 dark:text-white/90">Aturan &amp; Fleksibilitas Absensi (GPS / RFID / Jadwal)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Atur kebijakan lokasi GPS, kamera, validasi radius, jadwal siswa, dan hari aktif absensi tanpa hardcode.</p>
                    </div>
                    <a href="{{ route('admin.lokasi.index') }}" class="text-xs font-semibold text-brand-500 hover:text-brand-600">Kelola Titik Lokasi Sekolah &rarr;</a>
                </div>

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Mode Izin Titik Lokasi GPS</label>
                        <select name="mode_lokasi_gps"
                            class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm text-gray-800 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">
                            <option value="semua_lokasi" {{ old('mode_lokasi_gps', $pengaturan->mode_lokasi_gps ?? 'semua_lokasi') == 'semua_lokasi' ? 'selected' : '' }}>
                                Otomatis Semua Lokasi Sekolah (Semua Siswa, Guru &amp; Staff dapat absen di titik lokasi sekolah tanpa assign satu per satu)
                            </option>
                            <option value="khusus_pengguna" {{ old('mode_lokasi_gps', $pengaturan->mode_lokasi_gps ?? 'semua_lokasi') == 'khusus_pengguna' ? 'selected' : '' }}>
                                Hanya Lokasi yang Di-assign Khusus per Akun (Menu Lokasi)
                            </option>
                        </select>
                        @error('mode_lokasi_gps')
                            <p class="mt-1 text-sm text-error-500">{{ $message }}</p>
                        @enderror
                    </div>

                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3.5 cursor-pointer dark:border-gray-800 dark:bg-gray-900">
                        <input type="hidden" name="validasi_radius_gps" value="0">
                        <input type="checkbox" name="validasi_radius_gps" value="1"
                            {{ old('validasi_radius_gps', $pengaturan->validasi_radius_gps ?? true) ? 'checked' : '' }}
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        <div>
                            <div class="text-sm font-medium text-gray-800 dark:text-white/90">Validasi Radius Lokasi GPS</div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Jika aktif, wajib berada dalam radius titik lokasi sekolah. Nonaktifkan untuk mode bebas radius (PKL / Dinas Luar / WFH, koordinat tetap tercatat).</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3.5 cursor-pointer dark:border-gray-800 dark:bg-gray-900">
                        <input type="hidden" name="wajib_foto_gps" value="0">
                        <input type="checkbox" name="wajib_foto_gps" value="1"
                            {{ old('wajib_foto_gps', $pengaturan->wajib_foto_gps ?? true) ? 'checked' : '' }}
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        <div>
                            <div class="text-sm font-medium text-gray-800 dark:text-white/90">Wajib Foto Kamera Live saat Absen GPS</div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Jika aktif, pengguna wajib mengambil foto kamera saat check-in/out GPS. Nonaktifkan agar kamera bersifat opsional.</p>
                        </div>
                    </label>

                    <label class="flex items-start gap-3 rounded-lg border border-gray-200 bg-white p-3.5 cursor-pointer dark:border-gray-800 dark:bg-gray-900">
                        <input type="hidden" name="izinkan_absen_tanpa_jadwal" value="0">
                        <input type="checkbox" name="izinkan_absen_tanpa_jadwal" value="1"
                            {{ old('izinkan_absen_tanpa_jadwal', $pengaturan->izinkan_absen_tanpa_jadwal ?? true) ? 'checked' : '' }}
                            class="mt-1 h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                        <div>
                            <div class="text-sm font-medium text-gray-800 dark:text-white/90">Izinkan Absensi Siswa Tanpa Jadwal Pelajaran</div>
                            <p class="text-xs text-gray-500 dark:text-gray-400">Jika aktif, siswa tetap dapat check-in/out harian via GPS maupun RFID meskipun jadwal pelajaran hari itu belum dibuat.</p>
                        </div>
                    </label>

                    <div class="flex flex-col justify-center gap-2.5 rounded-lg border border-gray-200 bg-white p-3.5 dark:border-gray-800 dark:bg-gray-900">
                        <div class="text-sm font-medium text-gray-800 dark:text-white/90">Buka Absensi Akhir Pekan</div>
                        <div class="flex flex-wrap items-center gap-6">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="hidden" name="absensi_hari_sabtu" value="0">
                                <input type="checkbox" name="absensi_hari_sabtu" value="1"
                                    {{ old('absensi_hari_sabtu', $pengaturan->absensi_hari_sabtu ?? false) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                                <span>Hari Sabtu</span>
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
                                <input type="hidden" name="absensi_hari_minggu" value="0">
                                <input type="checkbox" name="absensi_hari_minggu" value="1"
                                    {{ old('absensi_hari_minggu', $pengaturan->absensi_hari_minggu ?? false) ? 'checked' : '' }}
                                    class="h-4 w-4 rounded border-gray-300 text-brand-500 focus:ring-brand-500" />
                                <span>Hari Minggu</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 flex items-center gap-3">
                <button type="submit"
                    class="bg-brand-500 shadow-theme-xs hover:bg-brand-600 inline-flex items-center justify-center rounded-lg px-4 py-2.5 text-sm font-medium text-white transition">
                    Simpan Pengaturan
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
