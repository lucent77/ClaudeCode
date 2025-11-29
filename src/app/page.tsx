import { redirect } from 'next/navigation'
import { auth } from '@/lib/auth'

export default async function HomePage() {
  const session = await auth()

  if (session) {
    // Redirect to appropriate page based on role
    if (session.user.role === 'ADMIN') {
      redirect('/dashboard')
    } else {
      redirect('/my-tasks')
    }
  }

  redirect('/login')
}
