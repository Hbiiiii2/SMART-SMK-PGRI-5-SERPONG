<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RaporArchive extends Model
{
    use HasFactory;

    protected $table = 'rapor_archives';

    protected $fillable = [
        'siswa_id',
        'tahun_ajaran_id',
        'semester',
        'jenis_rapor',
        'kelas',
        'rombel_id',
        'archive_number',
        'verification_token',
        'document_hash',
        'version',
        'payload',
        'finalized_by',
        'finalized_at',
        'reprint_count',
        'last_reprinted_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'finalized_at' => 'datetime',
        'last_reprinted_at' => 'datetime',
        'version' => 'integer',
        'reprint_count' => 'integer',
    ];

    public function siswa()
    {
        return $this->belongsTo(Siswa::class, 'siswa_id');
    }

    public function tahunAjaran()
    {
        return $this->belongsTo(TahunAjaran::class, 'tahun_ajaran_id');
    }

    public function rombel()
    {
        return $this->belongsTo(Rombel::class, 'rombel_id');
    }

    public function finalizer()
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }

    /**
     * Verify immutable snapshot SHA-256 integrity.
     */
    public function verifyIntegrity(): bool
    {
        if (empty($this->document_hash) || !is_array($this->payload)) {
            return false;
        }

        $computed = self::computeHash($this->payload);

        return hash_equals($this->document_hash, $computed);
    }

    /**
     * Canonical SHA-256 fingerprint for an archive payload array.
     */
    public static function computeHash(array $payload): string
    {
        return hash('sha256', (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
