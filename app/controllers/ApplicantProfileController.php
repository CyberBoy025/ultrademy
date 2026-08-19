<?php
declare(strict_types=1);

/**
 * Biodata / profile wizard (brief §12) — one section per screen rather than one giant
 * form, with a completion percentage recalculated after every save
 * (JobApplicantProfile::recalculateCompletion()). The profile is reusable across every
 * application, exactly like the shared `users`/`user_profiles` rows it partly builds on.
 */
final class ApplicantProfileController
{
    private static function requireLogin(): void
    {
        if (!Auth::check()) {
            header('Location: login.php');
            exit;
        }
    }

    // --------------------------------------------------------------------- personal

    public static function personal(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $user = Database::one('SELECT phone FROM users WHERE id = :u', ['u' => $userId]);
        $person = Database::one('SELECT * FROM user_profiles WHERE user_id = :u', ['u' => $userId]);
        $profile = JobApplicantProfile::forUser($userId);

        $main = View::render('careers/profile/personal', ['user' => $user, 'person' => $person, 'profile' => $profile]);
        View::careersShell('profile', 'My Profile — Personal Information', $main);
    }

    public static function personalSave(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();

        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $phone = trim((string) ($_POST['phone'] ?? ''));
        $address = trim((string) ($_POST['address_line'] ?? ''));
        $city = trim((string) ($_POST['city'] ?? ''));
        $state = trim((string) ($_POST['state'] ?? ''));
        $country = trim((string) ($_POST['country'] ?? 'Nigeria'));

        if ($firstName === '' || $lastName === '') {
            Session::flash('error', 'Enter your first and last name.');
            header('Location: app.php?r=profile.personal');
            exit;
        }

        Database::query('UPDATE users SET phone = :p WHERE id = :id', ['p' => $phone ?: null, 'id' => $userId]);
        Database::query(
            'UPDATE user_profiles SET first_name = :f, last_name = :l, address_line = :a, city = :c, state = :s, country = :co WHERE user_id = :u',
            ['f' => $firstName, 'l' => $lastName, 'a' => $address ?: null, 'c' => $city ?: null, 's' => $state ?: null, 'co' => $country ?: null, 'u' => $userId]
        );

        $summary = trim((string) ($_POST['professional_summary'] ?? ''));
        $occupation = trim((string) ($_POST['current_occupation'] ?? ''));
        $years = ($_POST['years_experience'] ?? '') !== '' ? (int) $_POST['years_experience'] : null;
        $interests = trim((string) ($_POST['career_interests'] ?? ''));
        JobApplicantProfile::updateSummary($userId, $summary, $occupation, $years, $interests);

        Session::flash('success', 'Personal information saved.');
        header('Location: app.php?r=profile.education');
        exit;
    }

    // -------------------------------------------------------------------- education

    public static function education(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $main = View::render('careers/profile/education', [
            'items' => ApplicantEducation::forUser($userId),
            'profile' => JobApplicantProfile::forUser($userId),
        ]);
        View::careersShell('profile', 'My Profile — Education', $main);
    }

    public static function educationStore(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $institution = trim((string) ($_POST['institution'] ?? ''));
        $qualification = trim((string) ($_POST['qualification'] ?? ''));
        if ($institution === '' || $qualification === '') {
            Session::flash('error', 'Enter the institution and qualification.');
        } else {
            ApplicantEducation::create(
                $userId, $institution, $qualification, trim((string) ($_POST['field_of_study'] ?? '')),
                $_POST['start_year'] !== '' ? (int) $_POST['start_year'] : null,
                $_POST['end_year'] !== '' ? (int) $_POST['end_year'] : null
            );
            JobApplicantProfile::recalculateCompletion($userId);
            Session::flash('success', 'Education added.');
        }
        header('Location: app.php?r=profile.education');
        exit;
    }

    public static function educationDelete(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        ApplicantEducation::delete((int) $_POST['id'], $userId);
        JobApplicantProfile::recalculateCompletion($userId);
        header('Location: app.php?r=profile.education');
        exit;
    }

    // ------------------------------------------------------------------- experience

    public static function experience(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $main = View::render('careers/profile/experience', [
            'items' => ApplicantExperience::forUser($userId),
            'profile' => JobApplicantProfile::forUser($userId),
        ]);
        View::careersShell('profile', 'My Profile — Work Experience', $main);
    }

    public static function experienceStore(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $org = trim((string) ($_POST['organisation'] ?? ''));
        $title = trim((string) ($_POST['job_title'] ?? ''));
        if ($org === '' || $title === '') {
            Session::flash('error', 'Enter the organisation and job title.');
        } else {
            ApplicantExperience::create(
                $userId, $org, $title, (string) ($_POST['employment_type'] ?? '') ?: null,
                (string) ($_POST['start_date'] ?? '') ?: null, (string) ($_POST['end_date'] ?? '') ?: null,
                isset($_POST['is_current']), trim((string) ($_POST['responsibilities'] ?? '')),
                trim((string) ($_POST['reason_for_leaving'] ?? ''))
            );
            JobApplicantProfile::recalculateCompletion($userId);
            Session::flash('success', 'Experience added.');
        }
        header('Location: app.php?r=profile.experience');
        exit;
    }

    public static function experienceDelete(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        ApplicantExperience::delete((int) $_POST['id'], $userId);
        JobApplicantProfile::recalculateCompletion($userId);
        header('Location: app.php?r=profile.experience');
        exit;
    }

    // ------------------------------------------------------------------------ skills

    public static function skills(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $main = View::render('careers/profile/skills', [
            'items' => ApplicantSkill::forUser($userId),
            'profile' => JobApplicantProfile::forUser($userId),
        ]);
        View::careersShell('profile', 'My Profile — Skills', $main);
    }

    public static function skillsStore(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $name = trim((string) ($_POST['skill_name'] ?? ''));
        if ($name === '') {
            Session::flash('error', 'Enter a skill name.');
        } else {
            ApplicantSkill::create($userId, $name, (string) ($_POST['skill_type'] ?? 'technical'), (string) ($_POST['proficiency'] ?? '') ?: null);
            JobApplicantProfile::recalculateCompletion($userId);
            Session::flash('success', 'Skill added.');
        }
        header('Location: app.php?r=profile.skills');
        exit;
    }

    public static function skillsDelete(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        ApplicantSkill::delete((int) $_POST['id'], $userId);
        JobApplicantProfile::recalculateCompletion($userId);
        header('Location: app.php?r=profile.skills');
        exit;
    }

    // ------------------------------------------------------------------ certifications

    public static function certifications(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $main = View::render('careers/profile/certifications', [
            'items' => ApplicantCertification::forUser($userId),
            'profile' => JobApplicantProfile::forUser($userId),
        ]);
        View::careersShell('profile', 'My Profile — Certifications', $main);
    }

    public static function certificationsStore(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Session::flash('error', 'Enter the certification name.');
        } else {
            $result = ApplicantCertification::create(
                $userId, $name, trim((string) ($_POST['issuing_organisation'] ?? '')),
                (string) ($_POST['issued_on'] ?? '') ?: null, (string) ($_POST['expires_on'] ?? '') ?: null,
                $_FILES['document'] ?? null
            );
            if (is_string($result)) {
                Session::flash('error', $result);
            } else {
                Session::flash('success', 'Certification added.');
            }
        }
        header('Location: app.php?r=profile.certifications');
        exit;
    }

    public static function certificationsDelete(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        ApplicantCertification::delete((int) $_POST['id'], (int) Auth::id());
        header('Location: app.php?r=profile.certifications');
        exit;
    }

    // ----------------------------------------------------------------------- resume

    public static function resume(): void
    {
        self::requireLogin();
        $main = View::render('careers/profile/resume', ['profile' => JobApplicantProfile::forUser((int) Auth::id())]);
        View::careersShell('profile', 'My Profile — Documents', $main);
    }

    public static function resumeUpload(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $error = JobApplicantProfile::uploadResume((int) Auth::id(), $_FILES['resume'] ?? []);
        Session::flash($error === null ? 'success' : 'error', $error ?? 'Résumé uploaded.');
        header('Location: app.php?r=profile.resume');
        exit;
    }

    // -------------------------------------------------------------------- references

    public static function references(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $main = View::render('careers/profile/references', [
            'items' => ApplicantReference::forUser($userId),
            'profile' => JobApplicantProfile::forUser($userId),
        ]);
        View::careersShell('profile', 'My Profile — References', $main);
    }

    public static function referencesStore(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        $name = trim((string) ($_POST['name'] ?? ''));
        if ($name === '') {
            Session::flash('error', 'Enter the reference\'s name.');
        } else {
            ApplicantReference::create(
                $userId, $name, trim((string) ($_POST['relationship'] ?? '')), trim((string) ($_POST['organisation'] ?? '')),
                trim((string) ($_POST['email'] ?? '')), trim((string) ($_POST['phone'] ?? ''))
            );
            JobApplicantProfile::recalculateCompletion($userId);
            Session::flash('success', 'Reference added.');
        }
        header('Location: app.php?r=profile.references');
        exit;
    }

    public static function referencesDelete(): void
    {
        self::requireLogin();
        Csrf::requireValid();
        $userId = (int) Auth::id();
        ApplicantReference::delete((int) $_POST['id'], $userId);
        JobApplicantProfile::recalculateCompletion($userId);
        header('Location: app.php?r=profile.references');
        exit;
    }

    // ------------------------------------------------------------------------ review

    public static function review(): void
    {
        self::requireLogin();
        $userId = (int) Auth::id();
        $main = View::render('careers/profile/review', [
            'user' => Database::one('SELECT email, phone FROM users WHERE id = :u', ['u' => $userId]),
            'person' => Database::one('SELECT * FROM user_profiles WHERE user_id = :u', ['u' => $userId]),
            'profile' => JobApplicantProfile::forUser($userId),
            'education' => ApplicantEducation::forUser($userId),
            'experience' => ApplicantExperience::forUser($userId),
            'skills' => ApplicantSkill::forUser($userId),
            'certifications' => ApplicantCertification::forUser($userId),
            'references' => ApplicantReference::forUser($userId),
        ]);
        View::careersShell('profile', 'My Profile — Review', $main);
    }
}
