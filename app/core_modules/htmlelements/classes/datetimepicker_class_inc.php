<?php
/**
 * Accessible shared date-and-time form control.
 *
 * The control accepts and returns local wall-clock values. Calling modules
 * convert at storage boundaries through timeanddateservice.
 *
 * @package htmlelements
 * @author Derek Keats
 */
if (empty($GLOBALS['kewl_entry_point_run'])) { die('You cannot view this page directly'); }

class datetimepicker extends ChisimbaObject
{
    /** @var string Base form-field name. */
    public $name = 'datetime';
    /** @var string Local date in Y-m-d form. */
    public $dateValue = '';
    /** @var string Local time in H:i form. */
    public $timeValue = '';
    /** @var string Visible date label. */
    public $dateLabel = 'Date';
    /** @var string Visible time label. */
    public $timeLabel = 'Time';
    /** @var bool Whether both controls are required. */
    public $required = true;

    public function init() {}

    /** Set the base name used for the paired request parameters. */
    public function setName($name)
    {
        $this->name = preg_replace('/[^A-Za-z0-9_-]/', '', (string) $name);
    }

    /** Set the local value displayed by the control. */
    public function setValue($value)
    {
        if ($value instanceof DateTimeInterface) {
            $this->dateValue = $value->format('Y-m-d');
            $this->timeValue = $value->format('H:i');
            return;
        }
        $parts = preg_split('/\s+/', trim((string) $value), 2);
        $this->dateValue = $parts[0] ?? '';
        $this->timeValue = isset($parts[1]) ? substr($parts[1], 0, 5) : '';
    }

    /** Configure the visible labels. */
    public function setLabels($dateLabel, $timeLabel)
    {
        $this->dateLabel = (string) $dateLabel;
        $this->timeLabel = (string) $timeLabel;
    }

    /** Render the shared native date and time controls. */
    public function show()
    {
        $escape = static fn($value) => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
        $dateId = $this->name . '-date';
        $timeId = $this->name . '-time';
        $required = $this->required ? ' required' : '';
        return '<div class="chisimba-date-time-picker">'
            . '<div><label for="' . $escape($dateId) . '">' . $escape($this->dateLabel) . '</label>'
            . '<input id="' . $escape($dateId) . '" type="date" name="' . $escape($this->name . '_date')
            . '" value="' . $escape($this->dateValue) . '"' . $required . '></div>'
            . '<div><label for="' . $escape($timeId) . '">' . $escape($this->timeLabel) . '</label>'
            . '<input id="' . $escape($timeId) . '" type="time" name="' . $escape($this->name . '_time')
            . '" value="' . $escape($this->timeValue) . '"' . $required . '></div>'
            . '</div>';
    }
}
?>
