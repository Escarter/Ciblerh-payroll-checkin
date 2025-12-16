#!/usr/bin/env python3
import random
import csv
from datetime import datetime, timedelta

# Employee names extracted from PDF
pdf_names = [
    "ABANA AMBROISE", "ABANGONG NZANG LESLIE PATRICIA", "ABENA ATANGANA ISIDORE",
    "ABENA EBE STEVE LOIC", "ABENAM BALLA FERDINAND LOIC", "ABOMO OWOUNDI VANESSA",
    "ADIDIGUE KATHIA MAYEVA", "AFIA YVES WILLIAM", "AMA MBONI PARICE ANICET",
    "AMANG A BAKO MICHEL ELVIS", "AMBADIANG BONOHO ETIENNE", "AMBASSA BOUNOUNG CHRISTIAN GUILLAUME",
    "AMOUGOU ZANG LUCIEN IV", "ASSAKO ABENA VICTOR NARCISSE", "ASSIGA GENEVIEVE GHISLAINE",
    "ATANGANA VALENTIN", "AWANA EBODE GUILLAUME ALEXIS", "AYE ZE AURLUS BERENGER",
    "AYIKA NJIALE AURORE PAMELA", "BALLA EFFALA JEAN", "BEBEDI EBELLE ROBERT RODRIGUE",
    "BESSALA VALERE MURIEL", "BIKANG ANTOINE", "BILKISSOU MOUSSA",
    "BIYONG JEAN NOEL", "BOYOMO ABAGA PATRICCE", "CHOUBOU FANMOE YVAN STEVE",
    "DEFO WAFO LUCIEN FABRICE", "DIN DIN CHRISTIAN LEBO", "DJOB NORBERT ARSENE",
    "DJULDENE GERMAINE", "EBOULE MARTIN PAUL", "ELOUNA ALBERT LITANO",
    "EMBOLO JEANNE CLAUDETTE", "ENDZODO JEAN CHRISTIAN", "ENGBWANG RENE CHARLIE",
    "EPOH THOMAS ERIC", "ERIC DESIRE IPOUK", "ESSAMA ESSAMA LIN ROSTAND",
    "ESSIANE THIERRY MONJOLI", "ETOUNGOU JEAN MARTIAL", "EVINA ABANDA AYME MAGLOIRE",
    "EWALIE ATANGANA CHRISTIANE AICHA", "EYEBE EYEBE BENJAMIN", "EYEBE MVOGO JEAN CLAUDE",
    "FOKA NEMBOT HERVE", "FOUDOP FOUTE ARMAND", "FRU THIERRY NJI",
    "GILLES PIVARD", "IMRANE BEN ISMAEL", "INDUIN MARIE ANGE",
    "INNA AISSATOU", "JAMPOU RONICK", "KAMLA CHEDJOU HERMAN JUDICCAEL",
    "KANGA BEKONO BRICE", "KITH NYAS JOSEPH DESIRE", "KOBLAH LARTEY CHARLES",
    "KOUAFFO MARXIM", "LAKO KAMENI YANNICK", "MAFFO OUOFO KELINE",
    "MAIMOUNA AYOUBA", "MAKUETCHE MIREILLE NANOU", "MANDOUMBI SCHWAMME GILLES ROGER",
    "MANGA ONGUENE CLAUDE DAVYNA", "MAPEMOUN JEREMIE BERTRAND", "MAYEGA ANDRE",
    "MBARGA FRANCOIS ACHILLE", "MBOCK SOM JOSEPH GHISLAIN", "MBOGE MBOGE PAUL ALAIN",
    "MEDANG ATEBA JACOB", "MEDENG ABEDENA THERESE CLEMENCE", "MEFIRE SYLVAIN ARNAUD",
    "MENGUE ME TYA SERGE DAVID", "MENOUNG BERNABE", "MESSI ABENA ISIDORE",
    "MVOA EVAGA ROMARIC", "MVONDO PETIT SERAPHIN B", "NAH RAYMOND STEPHANE",
    "NANGA CARINE ESTELLE", "NDA REGINE VANESSA", "NDALEU NDALEU DIMITRI JUNIOR",
    "NDINGA JOEL", "NDJODO EKANI PIERRE CLAVER", "NDO NTI EMMANUEL",
    "NDOOH ISABELLE SELARIE", "NDOUTOU MBONGUE GABRIELLE LAURE", "NDUM BENG DAMARIS",
    "NDZIE MARCEL PATRICK", "NGAGOUN WENDJI ROMARIC", "NGATSIN MBE BLAISE",
    "NGNABEYE TCHODOU CHRISTIAN MUR", "NGOULOU SALOMON CHANEL", "NGUIAMBA ALEXANDRE HONORE",
    "NJAMEN NJAMEN GABINE ARISTIDE", "NJAMPA GUIEKAM JOEL BRICE", "NJIKI PETGUEN PATRICK",
    "NJOCK SIMON BERTRAND", "NJOUME MILONG EMMANUEL RICHARD", "NKOCK GILBERT",
    "NKOU JUSTIN AIME", "NKUITCHOUA STEPHANE C", "NLEND PIERRE ADONIS",
    "NNENGUE FRANCOIS", "NNOMO CALVIN BERTRAND", "NOUSSITE FAKAM DIANE",
    "NSEKE KEDI MARIE MICHELLE", "NSEKE TOUBE EUGENE", "NTAMA AMBASSA DOMINIQUE SERGE",
    "NTEUNE NDJODOM FIDELE ARNOLD", "NTONGA DOUALA CHARLES EBENEZER", "NTSAMA KWEFEU BIJOU",
    "NYANDJOU CONSTANT", "NYASSENA AROUMBA ISMAEL ALAIN", "NZOFFOU TCHOUPOUA ANDAR PERLIN",
    "OKONABENG OMGBWA MARTHE AUDREY", "ONDOUA EVANG JEAN GUY MARCEL", "ONGBALIFOUNE CAROLE",
    "ONOGO MARIANNE", "OPPODO EKONO EMMANUELLE", "OWONA ANTOINE",
    "OWONA MESSI DIEUDONNE", "OYEBE ESSIGA MARTIN", "PEHA FELIX MICHEL",
    "PEKEKUE MBOUOMBOUO ABDOURAHAMAN", "POKAM DESAYUI CABREL", "REMO MEYOBEME HERVE MAURICE",
    "SAME CHRISTELLE CYNTHIA", "SEME ESSAMA BENOIT JOSEPH", "SENGUENA THOMAS CYRILLE",
    "SOUDJOU RIGIL VANECT", "TAMBO BLAISE", "TCHEUFFA HUBERT",
    "TCHOUAKEU SALVADOR", "TEDJONGMBA OLIVIER", "TEGIONA KEMEFO BILLY DALMAS",
    "THEMA NDANDO EDITH RITA", "TIAKO DJAMBOU DARIUS", "TOMO JOSELIN JOEL",
    "TSONDO MPESSE LEATICIA", "WAFO KUITCHEU SONNY ARITHSON", "WANDJI SILLE ELIE MIRABEAU",
    "WEHIWE RODOLPHE", "YANA CHRISTOPHE", "YANTOU TCHAMGUE HYACINTHE",
    "YEMELI MANFO HILAIRE BERTAND", "YENE JAMES HARRYS", "YOGO YOGO JEAN THEOPHILE",
    "YONGUE DIMITRI", "ZAMENGONO MFOULA ANDRE MARIE", "ZE ALAIN GIRESE CEDAR",
    "ZOBA JULES TOWA", "ZOGO MFEGUE RICHARD SYLVAIRE"
]

# Matricules from PDF
pdf_matricules = [
    "ENY022", "ENY351", "ENY043", "ENY247", "ENY229", "ENY003", "ENY006", "ENY282",
    "ENY016", "ENY307", "ENY333", "ENY071", "ENY072", "ENY368", "ENY316", "ENY250",
    "ENY267", "ENY281", "ENY027", "ENY345", "ENY074", "ENY369", "ENY353", "ENY383",
    "ENY075", "ENY374", "ENY010", "ENY057", "ENY028", "ENY044", "ENY233", "ENY378",
    "ENY029", "ENY234", "ENY309", "ENY077", "ENY364", "ENY347", "ENY308", "ENY017",
    "ENY360", "ENY279", "ENY362", "ENY302", "ENY008", "ENY059", "ENY361", "ENY045",
    "ENY098", "ENY344"
]

# Generate additional matricules if needed
def generate_matricule(index):
    if index < len(pdf_matricules):
        return pdf_matricules[index]
    # Generate new matricules in ENY format
    return f"ENY{400 + (index - len(pdf_matricules)):03d}"

# Departments and services
departments_services = [
    ("IT Department", "Development Team"),
    ("IT Department", "Project Management"),
    ("Human Resources", "Recruitment"),
    ("Human Resources", "HR Operations"),
    ("Finance", "Accounting"),
    ("Finance", "Budgeting"),
    ("Operations", "Field Operations"),
    ("Operations", "Customer Service"),
    ("Marketing", "Digital Marketing"),
    ("Marketing", "Brand Management"),
    ("Sales", "Direct Sales"),
    ("Sales", "Business Development")
]

# Positions
positions = [
    "Software Developer", "Project Manager", "HR Manager", "Accountant", "Operations Manager",
    "Marketing Specialist", "Sales Representative", "Business Analyst", "System Administrator",
    "Data Analyst", "Customer Service Representative", "Quality Assurance Engineer",
    "DevOps Engineer", "UI/UX Designer", "Technical Writer", "Database Administrator"
]

# Salary grades
salary_grades = ["Standard", "Senior", "Lead", "Manager", "Executive"]

# Email domains
email_domains = ["company.com", "corp.com", "enterprise.com", "business.com", "group.com"]

def generate_phone_number():
    # Generate US phone number in various formats
    formats = [
        lambda: f"+1.{random.randint(200,999)}.{random.randint(100,999)}.{random.randint(1000,9999)}",
        lambda: f"({random.randint(200,999)}) {random.randint(100,999)}-{random.randint(1000,9999)}",
        lambda: f"1-{random.randint(200,999)}-{random.randint(100,999)}-{random.randint(1000,9999)}",
        lambda: f"+1{random.randint(2000000000,9999999999)}"
    ]
    return random.choice(formats)()

def generate_birth_date():
    # Generate birth dates between 1980 and 2000
    start_date = datetime(1980, 1, 1)
    end_date = datetime(2000, 12, 31)
    delta = end_date - start_date
    random_days = random.randint(0, delta.days)
    birth_date = start_date + timedelta(days=random_days)
    return birth_date.strftime("%Y-%m-%d")

def split_name(full_name):
    parts = full_name.split()
    if len(parts) >= 2:
        first_name = parts[0]
        last_name = " ".join(parts[1:])
    else:
        first_name = full_name
        last_name = "Unknown"
    return first_name, last_name

def generate_employee(index):
    # Use PDF name if available, otherwise generate
    if index < len(pdf_names):
        full_name = pdf_names[index]
        first_name, last_name = split_name(full_name)
    else:
        # Generate additional names if we run out
        first_names = ["John", "Jane", "Michael", "Sarah", "David", "Emma", "James", "Lisa",
                      "Robert", "Maria", "William", "Jennifer", "Richard", "Patricia", "Charles", "Linda"]
        last_names = ["Smith", "Johnson", "Williams", "Brown", "Jones", "Garcia", "Miller",
                     "Davis", "Rodriguez", "Martinez", "Hernandez", "Lopez", "Gonzalez", "Wilson", "Anderson"]
        first_name = random.choice(first_names)
        last_name = random.choice(last_names)

    matricule = generate_matricule(index)
    email_domain = random.choice(email_domains)
    email = f"{first_name.lower()}.{last_name.lower().replace(' ', '.')}@{email_domain}"

    department, service = random.choice(departments_services)
    position = random.choice(positions)
    salary_grade = random.choice(salary_grades)

    # Salary based on grade and position
    base_salary = 40000 + (index * 50) + random.randint(0, 10000)
    if salary_grade == "Senior":
        base_salary *= 1.2
    elif salary_grade == "Lead":
        base_salary *= 1.4
    elif salary_grade == "Manager":
        base_salary *= 1.6
    elif salary_grade == "Executive":
        base_salary *= 2.0

    net_salary = int(base_salary)

    # Generate leave days (25-34 cycling)
    remaining_leave_days = 25 + (index % 10)

    # SMS notifications (0 or 1)
    sms_notifications = random.choice([0, 1])
    email_notifications = 1  # Always receive email

    employee = {
        "First Name": first_name,
        "Last Name": last_name,
        "Email": email,
        "Professional Phone Number": generate_phone_number(),
        "Matricule": matricule,
        "Position": position,
        "Net Salary": net_salary,
        "Salary Grade": salary_grade,
        "Contract End Date": "2026-12-31",
        "Department": department,
        "Service": service,
        "Role": "employee",
        "Status": 1,
        "Password": "password123",
        "Remaining Leave Days": remaining_leave_days,
        "Monthly Leave Allocation": 2.5,
        "Receive SMS Notifications": sms_notifications,
        "Personal Phone Number": generate_phone_number(),
        "Work Start Time": "08:00",
        "Work End Time": "17:00",
        "Receive Email Notifications": email_notifications,
        "Alternative Email": "",
        "Date of Birth": generate_birth_date()
    }

    return employee

# Generate 500 employees
employees = []
for i in range(500):
    employees.append(generate_employee(i))

# Write to CSV
with open("generated_employees.csv", "w", newline="", encoding="utf-8") as csvfile:
    fieldnames = [
        "First Name", "Last Name", "Email", "Professional Phone Number", "Matricule",
        "Position", "Net Salary", "Salary Grade", "Contract End Date", "Department",
        "Service", "Role", "Status", "Password", "Remaining Leave Days",
        "Monthly Leave Allocation", "Receive SMS Notifications", "Personal Phone Number",
        "Work Start Time", "Work End Time", "Receive Email Notifications",
        "Alternative Email", "Date of Birth"
    ]

    writer = csv.DictWriter(csvfile, fieldnames=fieldnames)
    writer.writeheader()

    for employee in employees:
        writer.writerow(employee)

print("Generated 500 employees in generated_employees.csv")
