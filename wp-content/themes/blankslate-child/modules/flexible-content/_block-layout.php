<?php
// toolkit
// 	goal: drive downloads
// 	3 top features
// 		what is revitalization
// 		how does revitalization work
// 		who can revitalize
// 	CTA
// 		select format
// 		select language
// 			select versions (latest implied)
// 		name, email
// 		newsletter
// 		download

// version
// 	language
// 		format

class DocumentVersion {
    public int $versionNumber;
    public array $languages = [];

    public function __construct(array $data) {
        $this->versionNumber = $data['version'] ?? 0;

        if (!empty($data['language_container'])) {
            foreach ($data['language_container'] as $langData) {
                $this->languages[] = new DocumentLanguage($langData);
            }
        }
    }

    public function getThumbnail(): string {
        return wp_get_attachment_url(get_field('thumbnail')) ?: '';
    }
}

class DocumentLanguage {
    public int $id;
    public string $name;
    public array $formats = [];

    public function __construct(array $data) {
        $this->id = $data['language']->ID ?? 0;
        $this->name = get_field('standard_name', $this->id) ?? 'Unknown Language';

        if (!empty($data['format_container'])) {
            foreach ($data['format_container'] as $formatData) {
                $this->formats[] = [
                    'format' => $formatData['format'] ?? '',
                    'file'   => $formatData['file'] ?? ''
                ];
            }
        }
    }
}

if(!function_exists('get_acf_documents')){
    function get_acf_documents(): array {
    $acf_data = get_sub_field('document');

    if (empty($acf_data) || !isset($acf_data['version_container'])) {
        return [];
    }

    return [new Document($acf_data)];
    }
}

$documents = get_acf_documents();
foreach ($documents as $document) {
    echo $document->renderCard();
    echo $document->renderModal();
}



class Document {
    public string $name;
    public array $versions = []; // Each version contains languages & formats

    public function __construct(array $data) {
        $this->name = $data['name'] ?? 'Unknown Document';

        // Parse versions
        if (!empty($data['version_container'])) {
            foreach ($data['version_container'] as $versionData) {
                $version = new DocumentVersion($versionData);
                $this->versions[$version->versionNumber] = $version;
            }
        }

        // Sort versions in descending order
        krsort($this->versions);
    }

    public function getLatestVersion(): ?DocumentVersion {
        return reset($this->versions) ?: null;
    }

    public function getAvailableLanguages(): array {
        $languages = [];
        foreach ($this->versions as $version) {
            foreach ($version->languages as $language) {
                $languages[$language->id] = $language->name;
            }
        }
        return array_values(array_unique($languages)); // Remove duplicates
    }

    public function renderCard(): string {
        $latestVersion = $this->getLatestVersion();
        if (!$latestVersion) return '';

        ob_start();
        ?>
        <section class="wt_content-block--thirds">
            <div class="wt_content-block__image" style="background-image:url('<?= esc_url($latestVersion->getThumbnail()) ?>');"></div>
            <aside class="wt_content-block__copy">
                <h1><?= esc_html($this->name) ?></h1>
                <p>Latest Version: <?= esc_html($latestVersion->versionNumber) ?></p>
                <button onclick="openModal('<?= esc_attr($this->name) ?>')">Download</button>
            </aside>
        </section>
        <?php
        return ob_get_clean();
    }

    public function renderModal(): string {
        $latestVersion = $this->getLatestVersion();
        if (!$latestVersion) return '';

        ob_start();
        ?>
        <div id="document-modal" class="modal">
            <h2><?= esc_html($this->name) ?></h2>
            <label>Version</label>
            <select id="version-select">
                <?php foreach ($this->versions as $version): ?>
                    <option value="<?= esc_attr($version->versionNumber) ?>"><?= esc_html($version->versionNumber) ?></option>
                <?php endforeach; ?>
            </select>

            <label>Language</label>
            <select id="language-select">
                <?php foreach ($latestVersion->languages as $language): ?>
                    <option value="<?= esc_attr($language->id) ?>"><?= esc_html($language->name) ?></option>
                <?php endforeach; ?>
            </select>

            <div id="download-options">
                <?php foreach ($latestVersion->languages as $language): ?>
                    <?php foreach ($language->formats as $format): ?>
                        <a href="<?= esc_url($format['file']) ?>" class="button"><?= esc_html($format['format']) ?></a>
                    <?php endforeach; ?>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}