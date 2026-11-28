@php
    $isEdit = isset($siswa) && $siswa->exists;
    $profil = $siswa->profil ?? null;
    $ayah = $isEdit ? $siswa->orangTuas->firstWhere('hubungan', 'ayah') : null;
    $ibu = $isEdit ? $siswa->orangTuas->firstWhere('hubungan', 'ibu') : null;
    $wali = $isEdit ? $siswa->orangTuas->firstWhere('hubungan', 'wali') : null;
    $periodik = $siswa->latestPeriodik ?? null;
    $inputCls = 'dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 focus:ring-brand-500/10 dark:focus:border-brand-800 h-11 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:ring-3 focus:outline-hidden dark:border-gray-700 dark:bg-gray-900 dark:text-white/90 dark:placeholder:text-white/30';
    $labelCls = 'mb-2 block text-sm font-medium text-gray-700 dark:text-gray-400';
@endphp

@if($errors->any())
    <div class="mb-6 rounded-xl border border-error-200 bg-error-50 p-4 text-sm text-error-700 dark:border-error-800 dark:bg-error-500/10 dark:text-error-400">
        <p class="font-semibold">Terdapat kesalahan pada input:</p>
        <ul class="mt-1 list-disc pl-5 text-xs">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div x-data="{ tab: 'identitas' }">
    {{-- Navigation Tabs --}}
    <div class="mb-6 flex flex-wrap gap-2 border-b border-gray-200 pb-3 dark:border-gray-800">
        @foreach([
            'identitas' => '1. Identitas & Akun',
            'domisili' => '2. Alamat & Domisili',
            'ortu' => '3. Orang Tua & Wali',
            'periodik' => '4. Periodik & Dokumen',
            'bantuan' => '5. KIP / PIP & Rekening',
        ] as $key => $label)
            <button type="button" @click="tab = '{{ $key }}'"
                :class="tab === '{{ $key }}' ? 'bg-brand-500 text-white shadow-theme-xs' : 'bg-gray-100 text-gray-600 hover:bg-gray-200 dark:bg-gray-800 dark:text-gray-300 dark:hover:bg-gray-700'"
                class="rounded-lg px-4 py-2 text-xs font-semibold transition">
                {{ $label }}
            </button>
        @endforeach
    </div>

    {{-- TAB 1: IDENTITAS & AKUN --}}
    <div x-show="tab === 'identitas'" class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="{{ $labelCls }}">Foto Profil</label>
            <div x-data="{ preview: '{{ $isEdit && $siswa->foto ? asset('storage/' . $siswa->foto) : '' }}' }" class="flex items-center gap-4">
                <div class="relative h-20 w-20 overflow-hidden rounded-full border border-gray-200 bg-gray-100 dark:border-gray-700 dark:bg-gray-800">
                    <img x-show="preview" :src="preview" class="h-full w-full object-cover" />
                    <span x-show="!preview" class="flex h-full w-full items-center justify-center text-xl font-bold text-gray-400">?</span>
                </div>
                <div class="flex-1">
                    <input type="file" name="foto" accept="image/*"
                        @change="if(event.target.files[0]) preview = URL.createObjectURL(event.target.files[0])"
                        class="w-full text-sm text-gray-500 file:mr-3 file:rounded-lg file:border-0 file:bg-brand-50 file:px-4 file:py-2 file:text-sm file:font-medium file:text-brand-600 hover:file:bg-brand-100 dark:file:bg-brand-500/10 dark:file:text-brand-400" />
                    <p class="mt-1 text-xs text-gray-400">JPG, PNG. Maks 2MB.</p>
                </div>
            </div>
        </div>

        <div>
            <label class="{{ $labelCls }}">Nama Lengkap <span class="text-error-500">*</span></label>
            <input type="text" name="nama_lengkap" value="{{ old('nama_lengkap', $siswa->nama_lengkap) }}" required class="{{ $inputCls }}" />
            @error('nama_lengkap') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <div class="mb-2 flex items-center justify-between">
                <label class="text-sm font-medium text-gray-700 dark:text-gray-400">Kelas / Rombel <span class="text-error-500">*</span></label>
                <a href="{{ route('admin.kelas.create') }}" class="text-xs font-medium text-brand-500 hover:underline">+ Kelola Master Kelas</a>
            </div>
            <select name="rombel_id" required class="{{ $inputCls }}">
                <option value="">-- Pilih Kelas / Rombel --</option>
                @foreach($rombels as $r)
                    <option value="{{ $r->id }}" {{ old('rombel_id', $siswa->rombel_id) == $r->id ? 'selected' : '' }}>
                        {{ $r->nama }} {{ $r->tahunAjaran ? '(' . $r->tahunAjaran->nama . ')' : '' }}
                    </option>
                @endforeach
            </select>
            @if($isEdit && !$siswa->rombel_id && $siswa->kelas)
                <input type="hidden" name="kelas" value="{{ $siswa->kelas }}">
                <p class="mt-1 text-xs text-amber-600">Kelas tersimpan sebelumnya: <strong>{{ $siswa->kelas }}</strong>. Silakan pilih rombel resmi di atas.</p>
            @endif
            @error('rombel_id') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
            @error('kelas') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $labelCls }}">Tahun Ajaran Masuk / Aktif <span class="text-error-500">*</span></label>
            <select name="tahun_ajaran_id" required class="{{ $inputCls }}">
                <option value="">Pilih Tahun Ajaran</option>
                @foreach($tahunAjarans as $ta)
                    <option value="{{ $ta->id }}" {{ old('tahun_ajaran_id', $siswa->tahun_ajaran_id ?? $tahunAjarans->firstWhere('is_aktif', true)?->id) == $ta->id ? 'selected' : '' }}>
                        {{ $ta->nama }} {{ $ta->is_aktif ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('tahun_ajaran_id') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $labelCls }}">Status Siswa</label>
            <select name="status_siswa" class="{{ $inputCls }}">
                @foreach($statusOptions as $val => $label)
                    <option value="{{ $val }}" {{ old('status_siswa', $siswa->status_siswa ?? 'aktif') === $val ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $labelCls }}">NISN <span class="text-xs text-gray-400">(Opsional)</span></label>
            <input type="text" name="nisn" value="{{ old('nisn', $siswa->nisn) }}" placeholder="10 digit NISN" class="{{ $inputCls }}" />
            @error('nisn') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $labelCls }}">NIPD / NIS Sekolah <span class="text-xs text-gray-400">(Opsional)</span></label>
            <input type="text" name="nipd" value="{{ old('nipd', $siswa->nipd) }}" placeholder="Contoh: 2627.001" class="{{ $inputCls }}" />
            @error('nipd') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $labelCls }}">NIK Siswa <span class="text-xs text-gray-400">(Opsional, 16 digit)</span></label>
            <input type="text" name="nik" value="{{ old('nik', $siswa->nik) }}" class="{{ $inputCls }}" />
        </div>

        <div>
            <label class="{{ $labelCls }}">Nomor KK <span class="text-xs text-gray-400">(Opsional)</span></label>
            <input type="text" name="no_kk" value="{{ old('no_kk', $siswa->no_kk) }}" class="{{ $inputCls }}" />
        </div>

        <div>
            <label class="{{ $labelCls }}">Jenis Kelamin <span class="text-xs text-gray-400">(Opsional)</span></label>
            <select name="jenis_kelamin" class="{{ $inputCls }}">
                <option value="">-- Pilih Jenis Kelamin --</option>
                <option value="L" {{ old('jenis_kelamin', $siswa->jenis_kelamin) == 'L' ? 'selected' : '' }}>Laki-laki</option>
                <option value="P" {{ old('jenis_kelamin', $siswa->jenis_kelamin) == 'P' ? 'selected' : '' }}>Perempuan</option>
            </select>
        </div>

        <div>
            <label class="{{ $labelCls }}">Agama <span class="text-xs text-gray-400">(Opsional)</span></label>
            <select name="agama" class="{{ $inputCls }}">
                <option value="">-- Pilih Agama --</option>
                @foreach($agamaOptions as $ag)
                    <option value="{{ $ag }}" {{ old('agama', $siswa->agama) === $ag ? 'selected' : '' }}>{{ $ag }}</option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="{{ $labelCls }}">Tempat Lahir <span class="text-xs text-gray-400">(Opsional)</span></label>
            <input type="text" name="tempat_lahir" value="{{ old('tempat_lahir', $siswa->tempat_lahir) }}" class="{{ $inputCls }}" />
        </div>

        <div>
            <label for="input-tanggal_lahir" class="{{ $labelCls }}">Tanggal Lahir <span class="text-xs text-gray-400">(Opsional)</span></label>
            <x-form.indo-date-input name="tanggal_lahir" :value="$siswa->tanggal_lahir" />
            @error('tanggal_lahir') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="{{ $labelCls }}">No. HP (Dapodik) <span class="text-xs text-gray-400">(Opsional)</span></label>
            <input type="text" name="no_telepon" value="{{ old('no_telepon', $siswa->no_telepon) }}" class="{{ $inputCls }}" />
        </div>

        <div>
            <label class="{{ $labelCls }}">No. WhatsApp Aktif (Lokal SMART+) <span class="text-xs text-gray-400">(Opsional, tidak ditimpa sync Dapodik)</span></label>
            <input type="text" name="no_wa_aktif" value="{{ old('no_wa_aktif', $siswa->no_wa_aktif) }}" placeholder="08xxxxxxxxxx" class="{{ $inputCls }}" />
        </div>

        <div>
            <label class="{{ $labelCls }}">Telepon Rumah <span class="text-xs text-gray-400">(Opsional)</span></label>
            <input type="text" name="telepon_rumah" value="{{ old('telepon_rumah', $siswa->telepon_rumah) }}" class="{{ $inputCls }}" />
        </div>

        <div>
            <label class="{{ $labelCls }}">Email Kontak / Orang Tua (Dapodik) <span class="text-xs text-gray-400">(Opsional)</span></label>
            <input type="email" name="email_pribadi" value="{{ old('email_pribadi', $siswa->email_pribadi) }}" placeholder="Email dari CSV/Dapodik (boleh sama antar saudara)" class="{{ $inputCls }}" />
            @error('email_pribadi') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
        </div>

        <div class="md:col-span-2 rounded-xl border border-gray-200 bg-gray-50/70 p-4 dark:border-gray-800 dark:bg-gray-900/40">
            <h4 class="text-sm font-semibold text-gray-800 dark:text-white/90">Akun Login Portal Siswa (Terpisah dari Email Dapodik)</h4>
            <p class="mt-0.5 mb-4 text-xs text-gray-500 dark:text-gray-400">Opsional: isi jika siswa membutuhkan akun login ke SMART+. Biarkan kosong bila hanya menyimpan data induk repository.</p>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                <div>
                    <label class="{{ $labelCls }}">Email Akun Login</label>
                    <input type="email" name="email" value="{{ old('email', $siswa->user?->email) }}" placeholder="nisn@siswa.smkpgri5serpong.sch.id" class="{{ $inputCls }}" />
                    @error('email') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelCls }}">Password @if($isEdit && $siswa->user) <span class="text-xs text-gray-400">(Kosongkan jika tetap)</span> @endif</label>
                    <input type="password" name="password" class="{{ $inputCls }}" />
                    @error('password') <p class="mt-1 text-sm text-error-500">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="{{ $labelCls }}">Konfirmasi Password</label>
                    <input type="password" name="password_confirmation" class="{{ $inputCls }}" />
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 2: ALAMAT & DOMISILI --}}
    <div x-show="tab === 'domisili'" x-cloak class="grid grid-cols-1 gap-6 md:grid-cols-2">
        <div class="md:col-span-2">
            <label class="{{ $labelCls }}">Alamat Lengkap Ringkas</label>
            <textarea name="alamat" rows="2" class="dark:bg-dark-900 shadow-theme-xs focus:border-brand-300 w-full rounded-lg border border-gray-300 bg-transparent px-4 py-2.5 text-sm text-gray-800 dark:border-gray-700 dark:bg-gray-900 dark:text-white/90">{{ old('alamat', $siswa->alamat) }}</textarea>
        </div>
        <div class="md:col-span-2">
            <label class="{{ $labelCls }}">Alamat Jalan / Kampung (Dapodik)</label>
            <input type="text" name="profil[alamat_jalan]" value="{{ old('profil.alamat_jalan', $profil?->alamat_jalan) }}" class="{{ $inputCls }}" />
        </div>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="{{ $labelCls }}">RT</label>
                <input type="text" name="profil[rt]" value="{{ old('profil.rt', $profil?->rt) }}" class="{{ $inputCls }}" />
            </div>
            <div>
                <label class="{{ $labelCls }}">RW</label>
                <input type="text" name="profil[rw]" value="{{ old('profil.rw', $profil?->rw) }}" class="{{ $inputCls }}" />
            </div>
        </div>
        <div>
            <label class="{{ $labelCls }}">Dusun / Lingkungan</label>
            <input type="text" name="profil[nama_dusun]" value="{{ old('profil.nama_dusun', $profil?->nama_dusun) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Kelurahan / Desa</label>
            <input type="text" name="profil[kelurahan]" value="{{ old('profil.kelurahan', $profil?->kelurahan) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Kecamatan</label>
            <input type="text" name="profil[kecamatan]" value="{{ old('profil.kecamatan', $profil?->kecamatan) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Kode Pos</label>
            <input type="text" name="profil[kode_pos]" value="{{ old('profil.kode_pos', $profil?->kode_pos) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Jenis Tinggal</label>
            <select name="profil[jenis_tinggal]" class="{{ $inputCls }}">
                <option value="">-- Pilih Jenis Tinggal --</option>
                @foreach($jenisTinggalOptions as $jt)
                    <option value="{{ $jt }}" {{ old('profil.jenis_tinggal', $profil?->jenis_tinggal) === $jt ? 'selected' : '' }}>{{ $jt }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">Alat Transportasi</label>
            <select name="profil[alat_transportasi]" class="{{ $inputCls }}">
                <option value="">-- Pilih Transportasi --</option>
                @foreach($transportasiOptions as $tr)
                    <option value="{{ $tr }}" {{ old('profil.alat_transportasi', $profil?->alat_transportasi) === $tr ? 'selected' : '' }}>{{ $tr }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="{{ $labelCls }}">Lintang (Latitude)</label>
            <input type="text" name="profil[lintang]" value="{{ old('profil.lintang', $profil?->lintang) }}" placeholder="-6.345..." class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Bujur (Longitude)</label>
            <input type="text" name="profil[bujur]" value="{{ old('profil.bujur', $profil?->bujur) }}" placeholder="106.672..." class="{{ $inputCls }}" />
        </div>
    </div>

    {{-- TAB 3: ORANG TUA & WALI --}}
    <div x-show="tab === 'ortu'" x-cloak class="space-y-6">
        @foreach(['ayah' => ['label' => 'Data Ayah Kandung', 'row' => $ayah], 'ibu' => ['label' => 'Data Ibu Kandung', 'row' => $ibu], 'wali' => ['label' => 'Data Wali', 'row' => $wali]] as $hub => $cfg)
            @php $row = $cfg['row']; @endphp
            <div class="rounded-xl border border-gray-200 p-4 dark:border-gray-800">
                <h4 class="mb-4 text-sm font-semibold text-gray-800 dark:text-white/90">{{ $cfg['label'] }}</h4>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                    <div>
                        <label class="{{ $labelCls }}">Nama</label>
                        <input type="text" name="ortu[{{ $hub }}][nama]" value="{{ old("ortu.{$hub}.nama", $row?->nama) }}" class="{{ $inputCls }}" />
                    </div>
                    <div>
                        <label class="{{ $labelCls }}">NIK</label>
                        <input type="text" name="ortu[{{ $hub }}][nik]" value="{{ old("ortu.{$hub}.nik", $row?->nik) }}" class="{{ $inputCls }}" />
                    </div>
                    <div>
                        <label class="{{ $labelCls }}">Tahun Lahir</label>
                        <input type="number" name="ortu[{{ $hub }}][tahun_lahir]" value="{{ old("ortu.{$hub}.tahun_lahir", $row?->tahun_lahir) }}" class="{{ $inputCls }}" />
                    </div>
                    <div>
                        <label class="{{ $labelCls }}">Jenjang Pendidikan</label>
                        <select name="ortu[{{ $hub }}][jenjang_pendidikan]" class="{{ $inputCls }}">
                            <option value="">-- Pilih --</option>
                            @foreach($pendidikanOptions as $p)
                                <option value="{{ $p }}" {{ old("ortu.{$hub}.jenjang_pendidikan", $row?->jenjang_pendidikan) === $p ? 'selected' : '' }}>{{ $p }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelCls }}">Pekerjaan</label>
                        <select name="ortu[{{ $hub }}][pekerjaan]" class="{{ $inputCls }}">
                            <option value="">-- Pilih --</option>
                            @foreach($pekerjaanOptions as $pk)
                                <option value="{{ $pk }}" {{ old("ortu.{$hub}.pekerjaan", $row?->pekerjaan) === $pk ? 'selected' : '' }}>{{ $pk }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelCls }}">Penghasilan Bulanan</label>
                        <select name="ortu[{{ $hub }}][penghasilan]" class="{{ $inputCls }}">
                            <option value="">-- Pilih --</option>
                            @foreach($penghasilanOptions as $ph)
                                <option value="{{ $ph }}" {{ old("ortu.{$hub}.penghasilan", $row?->penghasilan) === $ph ? 'selected' : '' }}>{{ $ph }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="{{ $labelCls }}">No. HP Orang Tua / Wali</label>
                        <input type="text" name="ortu[{{ $hub }}][no_hp]" value="{{ old("ortu.{$hub}.no_hp", $row?->no_hp) }}" class="{{ $inputCls }}" />
                    </div>
                    <div>
                        <label class="{{ $labelCls }}">Status</label>
                        <select name="ortu[{{ $hub }}][status_hidup]" class="{{ $inputCls }}">
                            <option value="hidup" {{ old("ortu.{$hub}.status_hidup", $row?->status_hidup ?? 'hidup') === 'hidup' ? 'selected' : '' }}>Masih Hidup</option>
                            <option value="meninggal" {{ old("ortu.{$hub}.status_hidup", $row?->status_hidup) === 'meninggal' ? 'selected' : '' }}>Sudah Meninggal</option>
                        </select>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- TAB 4: PERIODIK & DOKUMEN --}}
    <div x-show="tab === 'periodik'" x-cloak class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div>
            <label class="{{ $labelCls }}">Berat Badan (kg)</label>
            <input type="number" step="0.1" name="periodik[berat_badan]" value="{{ old('periodik.berat_badan', $periodik?->berat_badan) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Tinggi Badan (cm)</label>
            <input type="number" step="0.1" name="periodik[tinggi_badan]" value="{{ old('periodik.tinggi_badan', $periodik?->tinggi_badan) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Lingkar Kepala (cm)</label>
            <input type="number" step="0.1" name="periodik[lingkar_kepala]" value="{{ old('periodik.lingkar_kepala', $periodik?->lingkar_kepala) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Jumlah Saudara Kandung</label>
            <input type="number" name="periodik[jumlah_saudara_kandung]" value="{{ old('periodik.jumlah_saudara_kandung', $periodik?->jumlah_saudara_kandung) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Jarak Rumah ke Sekolah (km)</label>
            <input type="number" step="0.1" name="periodik[jarak_rumah_km]" value="{{ old('periodik.jarak_rumah_km', $periodik?->jarak_rumah_km) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Anak Ke-berapa</label>
            <input type="number" name="profil[anak_keberapa]" value="{{ old('profil.anak_keberapa', $profil?->anak_keberapa) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Sekolah Asal (SMP/MTs)</label>
            <input type="text" name="profil[sekolah_asal]" value="{{ old('profil.sekolah_asal', $profil?->sekolah_asal) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">No. Registrasi Akta Lahir</label>
            <input type="text" name="profil[no_registrasi_akta_lahir]" value="{{ old('profil.no_registrasi_akta_lahir', $profil?->no_registrasi_akta_lahir) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Kebutuhan Khusus</label>
            <input type="text" name="profil[kebutuhan_khusus]" value="{{ old('profil.kebutuhan_khusus', $profil?->kebutuhan_khusus ?? 'Tidak ada') }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">SKHUN</label>
            <input type="text" name="profil[skhun]" value="{{ old('profil.skhun', $profil?->skhun) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">No. Peserta Ujian Nasional</label>
            <input type="text" name="profil[no_peserta_un]" value="{{ old('profil.no_peserta_un', $profil?->no_peserta_un) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">No. Seri Ijazah</label>
            <input type="text" name="profil[no_seri_ijazah]" value="{{ old('profil.no_seri_ijazah', $profil?->no_seri_ijazah) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">UUID Peserta Didik Dapodik</label>
            <input type="text" name="dapodik_peserta_didik_id" value="{{ old('dapodik_peserta_didik_id', $siswa->dapodik_peserta_didik_id) }}" class="{{ $inputCls }} font-mono" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Tanggal Masuk Sekolah</label>
            <x-form.indo-date-input name="tanggal_masuk" :value="$siswa->tanggal_masuk" hint="" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Alasan Keluar / Mutasi</label>
            <input type="text" name="alasan_keluar" value="{{ old('alasan_keluar', $siswa->alasan_keluar) }}" class="{{ $inputCls }}" />
        </div>
    </div>

    {{-- TAB 5: KIP / PIP & REKENING --}}
    <div x-show="tab === 'bantuan'" x-cloak class="grid grid-cols-1 gap-6 md:grid-cols-3">
        <div class="flex items-center gap-3 pt-4">
            <input type="hidden" name="profil[penerima_kps]" value="0">
            <input type="checkbox" name="profil[penerima_kps]" value="1" {{ old('profil.penerima_kps', $profil?->penerima_kps) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-500" />
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Penerima KPS</label>
        </div>
        <div>
            <label class="{{ $labelCls }}">No. KPS</label>
            <input type="text" name="profil[no_kps]" value="{{ old('profil.no_kps', $profil?->no_kps) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Nomor KKS</label>
            <input type="text" name="profil[nomor_kks]" value="{{ old('profil.nomor_kks', $profil?->nomor_kks) }}" class="{{ $inputCls }}" />
        </div>

        <div class="flex items-center gap-3 pt-4">
            <input type="hidden" name="profil[penerima_kip]" value="0">
            <input type="checkbox" name="profil[penerima_kip]" value="1" {{ old('profil.penerima_kip', $profil?->penerima_kip) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-500" />
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Penerima KIP</label>
        </div>
        <div>
            <label class="{{ $labelCls }}">Nomor KIP</label>
            <input type="text" name="profil[nomor_kip]" value="{{ old('profil.nomor_kip', $profil?->nomor_kip) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Nama di KIP</label>
            <input type="text" name="profil[nama_di_kip]" value="{{ old('profil.nama_di_kip', $profil?->nama_di_kip) }}" class="{{ $inputCls }}" />
        </div>

        <div class="flex items-center gap-3 pt-4">
            <input type="hidden" name="profil[layak_pip]" value="0">
            <input type="checkbox" name="profil[layak_pip]" value="1" {{ old('profil.layak_pip', $profil?->layak_pip) ? 'checked' : '' }} class="h-4 w-4 rounded border-gray-300 text-brand-500" />
            <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Layak PIP (Usulan Sekolah)</label>
        </div>
        <div class="md:col-span-2">
            <label class="{{ $labelCls }}">Alasan Layak PIP</label>
            <input type="text" name="profil[alasan_layak_pip]" value="{{ old('profil.alasan_layak_pip', $profil?->alasan_layak_pip) }}" class="{{ $inputCls }}" />
        </div>

        <div>
            <label class="{{ $labelCls }}">Bank</label>
            <input type="text" name="profil[bank]" value="{{ old('profil.bank', $profil?->bank) }}" placeholder="Contoh: BANK BRI" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Nomor Rekening Bank</label>
            <input type="text" name="profil[nomor_rekening_bank]" value="{{ old('profil.nomor_rekening_bank', $profil?->nomor_rekening_bank) }}" class="{{ $inputCls }}" />
        </div>
        <div>
            <label class="{{ $labelCls }}">Rekening Atas Nama</label>
            <input type="text" name="profil[rekening_atas_nama]" value="{{ old('profil.rekening_atas_nama', $profil?->rekening_atas_nama) }}" class="{{ $inputCls }}" />
        </div>
    </div>
</div>
